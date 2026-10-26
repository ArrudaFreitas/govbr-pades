<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Pdf\Parser;

use ArrudaFreitas\GovbrPades\Pdf\Exceptions\InvalidPdfException;
use ArrudaFreitas\GovbrPades\Pdf\Exceptions\PdfLimitExceededException;
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
use PHPUnit\Framework\TestCase;

/**
 * @covers \ArrudaFreitas\GovbrPades\Pdf\Parser\ValueParser
 *
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Parser\Cursor
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Exceptions\InvalidPdfException
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Exceptions\PdfLimitExceededException
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
final class ValueParserTest extends TestCase
{
    public function testReadsNullAndBooleans(): void
    {
        self::assertSame(PdfNull::instance(), $this->parse('null'));
        self::assertTrue($this->boolean($this->parse('true'))->value());
        self::assertFalse($this->boolean($this->parse('false'))->value());
    }

    /**
     * @dataProvider integers
     */
    public function testReadsIntegers(string $text, int $expected): void
    {
        $value = $this->parse($text);

        self::assertInstanceOf(PdfInteger::class, $value);
        self::assertSame($expected, $value->value());
    }

    /**
     * @return array<string, array{string, int}>
     */
    public function integers(): array
    {
        return [
            'zero' => ['0', 0],
            'negative' => ['-42', -42],
            'explicit plus' => ['+17', 17],
            'beyond 2^53, still exact' => ['9007199254740993', 9007199254740993],
        ];
    }

    /**
     * @dataProvider reals
     */
    public function testReadsReals(string $text, float $expected): void
    {
        $value = $this->parse($text);

        self::assertInstanceOf(PdfReal::class, $value);
        self::assertSame($expected, $value->value());
    }

    /**
     * @return array<string, array{string, float}>
     */
    public function reals(): array
    {
        return [
            'trailing dot (ISO 32000-1, 7.3.3 lists "4.")' => ['595.', 595.0],
            'leading dot' => ['.5', 0.5],
            'negative, leading dot' => ['-.002', -0.002],
            'exponent, not legal but read as PDFBox does' => ['5.95e2', 595.0],
        ];
    }

    public function testKeepsTheSignOfNegativeZero(): void
    {
        $value = $this->parse('-0.0');

        self::assertInstanceOf(PdfReal::class, $value);
        self::assertSame('-0', (string) $value->value());
    }

    public function testReadsAReference(): void
    {
        $value = $this->parse("12 0 R");

        self::assertInstanceOf(PdfReference::class, $value);
        self::assertSame(12, $value->number());
        self::assertSame(0, $value->generation());

        $value = $this->parse("3\n7\rR");

        self::assertInstanceOf(PdfReference::class, $value);
        self::assertSame(7, $value->generation());
    }

    public function testAnRThatStartsAWordIsNotAReference(): void
    {
        $cursor = new Cursor('2 0 Rotate');
        $value = (new ValueParser())->parse($cursor);

        self::assertInstanceOf(PdfInteger::class, $value);
        self::assertSame(2, $value->value());
        self::assertSame(1, $cursor->position());
    }

    public function testNumbersInARowAreNotAReference(): void
    {
        $array = $this->array($this->parse('[0 0 595 842]'));

        self::assertCount(4, $array);
        self::assertContainsOnlyInstancesOf(PdfInteger::class, $array->items());
    }

    /**
     * @dataProvider names
     */
    public function testReadsNames(string $text, string $expected): void
    {
        $value = $this->parse($text);

        self::assertInstanceOf(PdfName::class, $value);
        self::assertSame($expected, $value->value());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function names(): array
    {
        return [
            'plain' => ['/Type', 'Type'],
            'empty name' => ['/ ', ''],
            '#xx decoded (ISO 32000-1, 7.3.5)' => ['/Ty#70e', 'Type'],
            '#20 is a space' => ['/A#20B', 'A B'],
            'malformed # stays literal' => ['/A#zzB', 'A#zzB'],
            'truncated # stays literal' => ['/A#2', 'A#2'],
        ];
    }

    public function testNamesNeedNoSpaceBetweenThem(): void
    {
        $array = $this->array($this->parse('[/Type/Page/]'));

        $names = array_map(
            function (PdfValue $item): string {
                return $this->nameOf($item);
            },
            $array->items()
        );

        self::assertSame(['Type', 'Page', ''], $names);
    }

    /**
     * @dataProvider literalStrings
     */
    public function testReadsLiteralStringsAsTheyAreWritten(string $text, string $expected): void
    {
        $value = $this->parse($text);

        self::assertInstanceOf(PdfString::class, $value);
        self::assertFalse($value->isHex());
        self::assertSame($expected, $value->text());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function literalStrings(): array
    {
        return [
            'empty' => ['()', ''],
            'balanced parentheses' => ['(a (b) c)', 'a (b) c'],
            'an escaped parenthesis does not count' => ['(a \\) b)', 'a \\) b'],
            'escape kept as two characters' => ['(line\\nbreak)', 'line\\nbreak'],
            'raw line break kept' => ["(l1\r\nl2)", "l1\r\nl2"],
        ];
    }

    public function testRefusesALiteralStringThatRunsOffTheEnd(): void
    {
        $this->expectException(InvalidPdfException::class);
        $this->expectExceptionMessage(
            'Literal string starting at byte 0 does not close (1 parenthesis(es) still open).'
        );

        $this->parse('(a (b)');
    }

    public function testRefusesAnEscapeAtTheEndOfTheText(): void
    {
        $this->expectException(InvalidPdfException::class);
        $this->expectExceptionMessage('Escape "\\" at the end of the text, byte 4.');

        $this->parse('(abc\\');
    }

    /**
     * @dataProvider hexStrings
     */
    public function testReadsHexadecimalStrings(string $text, string $expected): void
    {
        $value = $this->parse($text);

        self::assertInstanceOf(PdfString::class, $value);
        self::assertTrue($value->isHex());
        self::assertSame($expected, $value->text());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function hexStrings(): array
    {
        return [
            'empty' => ['<>', ''],
            'inner whitespace ignored' => ["<48 65\n6C\t6C>", '48656C6C'],
            'odd digit completed' => ['<ABC>', 'ABC0'],
            'case kept' => ['<aB>', 'aB'],
        ];
    }

    public function testRefusesANonHexadecimalDigit(): void
    {
        $this->expectException(InvalidPdfException::class);
        $this->expectExceptionMessage("Hexadecimal string starting at byte 0 has a non-hexadecimal character 'G'.");

        $this->parse('<4G>');
    }

    public function testRefusesAHexadecimalStringThatDoesNotClose(): void
    {
        $this->expectException(InvalidPdfException::class);
        $this->expectExceptionMessage("Hexadecimal string starting at byte 0 does not close with '>'.");

        $this->parse('<48');
    }

    public function testReadsArrays(): void
    {
        self::assertCount(0, $this->array($this->parse('[]')));
        self::assertCount(2, $this->array($this->parse('[1 2]')));
        self::assertCount(1, $this->array($this->parse('[12]')));

        $nested = $this->array($this->parse('[1 [2 [3]]]'));

        self::assertCount(2, $nested);
        self::assertCount(2, $this->array($nested->get(1)));
    }

    public function testRefusesAnArrayThatRunsOffTheEnd(): void
    {
        $this->expectException(InvalidPdfException::class);
        $this->expectExceptionMessage('Array does not close with "]" before the text ends.');

        $this->parse('[1 2');
    }

    public function testReadsDictionariesInTheOrderTheKeysArrive(): void
    {
        self::assertCount(0, $this->dictionary($this->parse('<<>>')));

        $dictionary = $this->dictionary($this->parse('<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]>>'));

        self::assertSame(['Type', 'Parent', 'MediaBox'], $dictionary->keys());
        self::assertInstanceOf(PdfReference::class, $dictionary->get('Parent'));
        self::assertSame([], $dictionary->repeatedKeys());
    }

    public function testReadsCommentsInsideADictionaryAsWhitespace(): void
    {
        $dictionary = $this->dictionary($this->parse("<< /Type /Catalog % the catalogue\n /Pages 2 0 R >>"));

        self::assertSame(['Type', 'Pages'], $dictionary->keys());
    }

    public function testARepeatedKeyKeepsTheLastValueAndIsRecorded(): void
    {
        $dictionary = $this->dictionary($this->parse('<< /Type /Pages /Count 1 /Type /Page /Type /Page >>'));

        self::assertSame('Page', $this->nameOf($dictionary->get('Type')));
        self::assertSame(['Type', 'Count'], $dictionary->keys());
        self::assertSame(['Type' => 3], $dictionary->repeatedKeys());
    }

    public function testStopsRightAfterTheValue(): void
    {
        $cursor = new Cursor("  % leading comment\n<< /A 1 >> endobj");
        (new ValueParser())->parse($cursor);

        self::assertSame(' endobj', substr("  % leading comment\n<< /A 1 >> endobj", $cursor->position()));
    }

    /**
     * @dataProvider keywordsAfterADictionary
     */
    public function testClosesADictionaryWhereTheNextKeywordStarts(string $keyword): void
    {
        $text = "<< /Length 5\n" . $keyword;
        $cursor = new Cursor($text);
        $parser = new ValueParser();

        $dictionary = $this->dictionary($parser->parse($cursor));

        self::assertSame(['Length'], $dictionary->keys());
        self::assertSame(strpos($text, $keyword), $cursor->position());
        self::assertCount(1, $parser->warnings());
        self::assertStringContainsString('does not close with ">>"', $parser->warnings()[0]);
    }

    /**
     * @return array<string, array{string}>
     */
    public function keywordsAfterADictionary(): array
    {
        return [
            'endobj' => ['endobj'],
            'stream' => ['stream'],
            'endstream' => ['endstream'],
        ];
    }

    public function testRefusesADictionaryThatRunsOffTheEnd(): void
    {
        $this->expectException(InvalidPdfException::class);
        $this->expectExceptionMessage('Dictionary does not close with ">>" before the text ends.');

        $this->parse('<< /Type /Page');
    }

    public function testDiscardsOneValueThatStandsWhereAKeyShouldBe(): void
    {
        $parser = new ValueParser();
        $dictionary = $this->dictionary($parser->parse(new Cursor('<< 5 /Foo 1 <<>> /Bar 2 >>')));

        self::assertSame(['Foo', 'Bar'], $dictionary->keys());
        self::assertSame(
            [
                'Dictionary at byte 3: a value of type integer stood where a key name was expected; discarded,'
                . ' as the reference does.',
                'Dictionary at byte 12: a value of type dictionary stood where a key name was expected; discarded,'
                . ' as the reference does.',
            ],
            $parser->warnings()
        );
    }

    /**
     * @dataProvider notEvenAValue
     */
    public function testRefusesWhatIsNotEvenAValue(string $text, string $expectedMessage): void
    {
        $this->expectException(InvalidPdfException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->parse($text);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function notEvenAValue(): array
    {
        return [
            'a closing parenthesis' => [')', "Unrecognised value at byte 0: ')'."],
            'nothing at all' => ['', "Unrecognised value at byte 0: ''."],
            'a keyword glued to a word' => ['nullx', "Unrecognised value at byte 0: 'nullx'."],
            'keywords are case sensitive' => ['True', "Unrecognised value at byte 0: 'True'."],
            'garbage where a key should be' => ['<< ) /Foo 1 >>', "Unrecognised value at byte 3: ') /Foo 1'."],
            'a keyword glued to a word, in a dictionary' => ['<< /A 1 endobjX >>', 'Unrecognised value at byte 8'],
        ];
    }

    public function testAcceptsNestingUpToTheLimit(): void
    {
        $text = str_repeat('[', ValueParser::MAXIMUM_DEPTH) . str_repeat(']', ValueParser::MAXIMUM_DEPTH);

        self::assertInstanceOf(PdfArray::class, $this->parse($text));
    }

    public function testRefusesNestingBeyondTheLimitBeforeTheStackOverflows(): void
    {
        $this->expectException(PdfLimitExceededException::class);
        $this->expectExceptionMessage('Value nesting goes beyond 256 levels at byte 256;');

        $this->parse(str_repeat('[', 400000));
    }

    public function testARefusalGivesTheDepthBudgetBack(): void
    {
        $parser = new ValueParser();

        try {
            $parser->parse(new Cursor(str_repeat('[', ValueParser::MAXIMUM_DEPTH + 1)));
            self::fail('The first reading should have been refused.');
        } catch (PdfLimitExceededException $refusal) {
        }

        $text = str_repeat('[', ValueParser::MAXIMUM_DEPTH) . str_repeat(']', ValueParser::MAXIMUM_DEPTH);

        self::assertInstanceOf(PdfArray::class, $parser->parse(new Cursor($text)));
    }

    public function testWarningsAccumulateAcrossReadings(): void
    {
        $parser = new ValueParser();

        $parser->parse(new Cursor('<< 5 /A 1 >>'));
        $parser->parse(new Cursor('<< /A 1 endobj'));

        self::assertCount(2, $parser->warnings());
    }

    /**
     * @dataProvider brokenContents
     */
    public function testAcceptsAnIllegibleContentsOnlyInASignature(string $text, bool $accepted): void
    {
        $parser = new ValueParser();

        try {
            $dictionary = $this->dictionary($parser->parse(new Cursor($text)));
        } catch (InvalidPdfException $refusal) {
            self::assertFalse($accepted, 'Refused, and should have been accepted: ' . $refusal->getMessage());
            self::assertStringContainsString("non-hexadecimal character 'G'", $refusal->getMessage());

            return;
        }

        self::assertTrue($accepted, 'Accepted, and should have been refused.');

        $contents = $dictionary->get('Contents');

        self::assertInstanceOf(PdfString::class, $contents);
        self::assertTrue($contents->isIllegible());
        self::assertSame('30G1', $contents->text());
        self::assertSame(strrpos($text, '<30G1>'), $contents->illegibleAt());
        self::assertSame('G', $contents->illegibleCharacter());
        self::assertCount(1, $parser->warnings());
        self::assertStringStartsWith('Old signature unreadable', $parser->warnings()[0]);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public function brokenContents(): array
    {
        return [
            'signature with /Type /Sig' => ['<< /Type /Sig /ByteRange [0 1 2 3] /Contents <30G1> >>', true],
            'signature without /Type, by its /ByteRange' => [
                '<< /Filter /Adobe.PPKLite /ByteRange [0 1 2 3] /Contents <30G1> >>',
                true,
            ],
            'document timestamp' => ['<< /Type /DocTimeStamp /Contents <30G1> >>', true],
            '/Type as a literal string, as the original compared it' => ['<< /Type (Sig) /Contents <30G1> >>', true],
            'repeated /Contents, the illegible one last' => [
                '<< /Type /Sig /ByteRange [0 1 2 3] /Contents <3001> /Contents <30G1> >>',
                true,
            ],
            'annotation' => ['<< /Type /Annot /Subtype /Text /Contents <30G1> >>', false],
            '/ID, not /Contents' => ['<< /Size 3 /ID [<30G1> <3001>] >>', false],
            'repeated /Contents, the legible one last' => [
                '<< /Type /Sig /ByteRange [0 1 2 3] /Contents <30G1> /Contents <3001> >>',
                false,
            ],
            '/Type as a hexadecimal string' => ['<< /Type <536967> /Contents <30G1> >>', false],
            'a dictionary inside a signature is not the signature' => [
                '<< /Type /Sig /ByteRange [0 1 2 3] /V << /Contents <30G1> >> >>',
                false,
            ],
        ];
    }

    public function testALegibleContentsIsAnOrdinaryHexadecimalString(): void
    {
        $parser = new ValueParser();
        $dictionary = $this->dictionary(
            $parser->parse(new Cursor('<< /Type /Sig /ByteRange [0 1 2 3] /Contents <3001> >>'))
        );
        $contents = $dictionary->get('Contents');

        self::assertInstanceOf(PdfString::class, $contents);
        self::assertFalse($contents->isIllegible());
        self::assertSame('3001', $contents->text());
        self::assertSame([], $parser->warnings());
    }

    public function testContentsMayHoldAnyOtherValue(): void
    {
        $dictionary = $this->dictionary($this->parse('<< /Contents << /A 1 >> /B /Contents >>'));

        self::assertInstanceOf(PdfDictionary::class, $dictionary->get('Contents'));
        self::assertSame('Contents', $this->nameOf($dictionary->get('B')));

        $dictionary = $this->dictionary($this->parse('<< /Contents 5 0 R >>'));

        self::assertInstanceOf(PdfReference::class, $dictionary->get('Contents'));
    }

    private function parse(string $text): PdfValue
    {
        return (new ValueParser())->parse(new Cursor($text));
    }

    private function boolean(PdfValue $value): PdfBoolean
    {
        self::assertInstanceOf(PdfBoolean::class, $value);

        return $value;
    }

    private function array(?PdfValue $value): PdfArray
    {
        self::assertInstanceOf(PdfArray::class, $value);

        return $value;
    }

    private function dictionary(PdfValue $value): PdfDictionary
    {
        self::assertInstanceOf(PdfDictionary::class, $value);

        return $value;
    }

    private function nameOf(?PdfValue $value): string
    {
        self::assertInstanceOf(PdfName::class, $value);

        return $value->value();
    }
}
