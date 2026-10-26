<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Pdf\Writer;

use ArrudaFreitas\GovbrPades\Pdf\Parser\Cursor;
use ArrudaFreitas\GovbrPades\Pdf\Parser\ValueParser;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfArray;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfBoolean;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfDictionary;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfInteger;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfName;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfNull;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfReal;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfReference;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfString;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfValue;
use ArrudaFreitas\GovbrPades\Pdf\Writer\ValueSerializer;
use ArrudaFreitas\GovbrPades\Test\Pdf\CanonicalForm;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ArrudaFreitas\GovbrPades\Pdf\Writer\ValueSerializer
 *
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Parser\Cursor
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Parser\ValueParser
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Value\PdfArray
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Value\PdfBoolean
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Value\PdfDictionary
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Value\PdfInteger
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Value\PdfName
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Value\PdfNull
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Value\PdfReal
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Value\PdfReference
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Value\PdfString
 */
final class ValueSerializerTest extends TestCase
{
    /**
     * @dataProvider names
     */
    public function testEscapesWhatANameCannotHoldLiterally(string $name, string $expected): void
    {
        self::assertSame($expected, (new ValueSerializer())->serialize(PdfName::of($name)));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function names(): array
    {
        return [
            'plain' => ['Type', '/Type'],
            'empty' => ['', '/'],
            'space' => ['A B', '/A#20B'],
            '# itself, or it comes back decoded' => ['a#b', '/a#23b'],
            'delimiters' => ['a(b)c/d', '/a#28b#29c#2Fd'],
            'bytes above 0x7E' => ["caf\xC3\xA9", '/caf#C3#A9'],
            'control byte' => ["a\x00b", '/a#00b'],
        ];
    }

    /**
     * @dataProvider reals
     */
    public function testWritesRealsPositionallyAndWithoutLosingTheirType(float $value, string $expected): void
    {
        self::assertSame($expected, (new ValueSerializer())->serialize(PdfReal::of($value)));
    }

    /**
     * @return array<string, array{float, string}>
     */
    public function reals(): array
    {
        return [
            'whole, keeps the dot' => [595.0, '595.0'],
            'tiny, no exponent' => [0.00001, '0.00001'],
            'huge, no exponent' => [1.0e20, '100000000000000000000.0'],
            'imprecise, every place it needs' => [0.1 + 0.2, '0.30000000000000004'],
            'negative zero keeps its sign' => [-0.0, '-0.0'],
            'negative' => [-841.89, '-841.89'],
        ];
    }

    /**
     * @dataProvider unwritableReals
     */
    public function testRefusesARealPdfCannotRepresent(float $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A real has no PDF representation');

        (new ValueSerializer())->serialize(PdfReal::of($value));
    }

    /**
     * @return array<string, array{float}>
     */
    public function unwritableReals(): array
    {
        return [
            'not a number' => [NAN],
            'infinity' => [INF],
            'negative infinity' => [-INF],
        ];
    }

    public function testRefusesARealTooSmallToBeWrittenPositionally(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A real cannot be written positionally in 40 places');

        (new ValueSerializer())->serialize(PdfReal::of(1.0e-50));
    }

    /**
     * @dataProvider values
     */
    public function testWritesEveryKindOfValue(PdfValue $value, string $expected): void
    {
        self::assertSame($expected, (new ValueSerializer())->serialize($value));
    }

    /**
     * @return array<string, array{PdfValue, string}>
     */
    public function values(): array
    {
        return [
            'null' => [PdfNull::instance(), 'null'],
            'true' => [PdfBoolean::of(true), 'true'],
            'false' => [PdfBoolean::of(false), 'false'],
            'negative integer' => [PdfInteger::of(-42), '-42'],
            'reference' => [PdfReference::to(3, 7), '3 7 R'],
            'literal string, written as it was read' => [PdfString::literal('a \\(b\\) c'), '(a \\(b\\) c)'],
            'hexadecimal string, case kept' => [PdfString::hex('0aFF'), '<0aFF>'],
            'empty array' => [PdfArray::of([]), '[]'],
            '"[1 2]" and "[12]" are different things' => [
                PdfArray::of([PdfInteger::of(1), PdfInteger::of(2)]),
                '[1 2]',
            ],
            'empty dictionary' => [PdfDictionary::of([]), '<<>>'],
            'dictionary, in the order of arrival' => [
                PdfDictionary::of(['Type' => PdfName::of('Page'), 'Count' => PdfInteger::of(1)]),
                '<</Type /Page /Count 1>>',
            ],
            'a key is escaped like any name' => [
                PdfDictionary::of(['Two Words' => PdfInteger::of(1)]),
                '<</Two#20Words 1>>',
            ],
        ];
    }

    public function testRefusesToWriteAnIllegibleString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is never written back');

        (new ValueSerializer())->serialize(
            PdfDictionary::of(['Contents' => PdfString::illegibleHex('30G1', 0, 'G')])
        );
    }

    public function testRefusesAValueClassItDoesNotKnow(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unknown PDF value class');

        (new ValueSerializer())->serialize(new class () implements PdfValue {
        });
    }

    /**
     * @dataProvider roundTrips
     */
    public function testReadingWhatWasWrittenGivesTheSameValueBack(PdfValue $value): void
    {
        $text = (new ValueSerializer())->serialize($value);
        $back = (new ValueParser())->parse(new Cursor($text));

        self::assertSame(CanonicalForm::of($value), CanonicalForm::of($back), 'Written as: ' . $text);
    }

    /**
     * @return array<string, array{PdfValue}>
     */
    public function roundTrips(): array
    {
        return [
            'name with a space' => [PdfName::of('A B')],
            'name with #' => [PdfName::of('a#b')],
            'name with delimiters' => [PdfName::of('a(b)c/d')],
            'name with bytes above 0x7E' => [PdfName::of("caf\xC3\xA9")],
            'empty name' => [PdfName::of('')],
            'literal with parentheses' => [PdfString::literal('a (b) c')],
            'literal with backslashes' => [PdfString::literal('c:\\\\temp\\\\x')],
            'literal with CR' => [PdfString::literal("line1\rline2")],
            'literal with CRLF' => [PdfString::literal("line1\r\nline2")],
            'empty literal' => [PdfString::literal('')],
            'empty hexadecimal' => [PdfString::hex('')],
            'empty dictionary' => [PdfDictionary::of([])],
            'empty array' => [PdfArray::of([])],
            'null' => [PdfNull::instance()],
            'false' => [PdfBoolean::of(false)],
            'tiny real' => [PdfReal::of(0.00001)],
            'huge real' => [PdfReal::of(1.0e20)],
            'imprecise real' => [PdfReal::of(0.1 + 0.2)],
            'negative zero' => [PdfReal::of(-0.0)],
            'negative integer' => [PdfInteger::of(-42)],
            'key with a space' => [PdfDictionary::of(['Two Words' => PdfInteger::of(1)])],
            'nested' => [
                PdfArray::of([
                    PdfDictionary::of(['A' => PdfArray::of([PdfReference::to(3, 7)])]),
                ]),
            ],
        ];
    }

    public function testARepeatedKeyIsNotCarriedIntoTheOutput(): void
    {
        $read = PdfDictionary::withRepeatedKeys(['Type' => PdfName::of('Page')], ['Type' => 2]);
        $text = (new ValueSerializer())->serialize($read);
        $back = (new ValueParser())->parse(new Cursor($text));

        self::assertSame('<</Type /Page>>', $text);
        self::assertInstanceOf(PdfDictionary::class, $back);
        self::assertSame([], $back->repeatedKeys());
    }

    /**
     * @dataProvider rawTexts
     */
    public function testRawTextComesBackAsTheSameBytes(string $raw): void
    {
        $text = (new ValueSerializer())->serialize(PdfString::fromBytes($raw));
        $back = (new ValueParser())->parse(new Cursor($text));

        self::assertSame('s:' . bin2hex($raw), CanonicalForm::of($back), 'Written as: ' . $text);
    }

    /**
     * @return array<string, array{string}>
     */
    public function rawTexts(): array
    {
        return [
            'raw parentheses' => ['a (b) c'],
            'closes first' => ['a ) b ( c'],
            'backslash at the end' => ['c:\\temp\\'],
            'CR and LF' => ["l1\r\nl2"],
        ];
    }
}
