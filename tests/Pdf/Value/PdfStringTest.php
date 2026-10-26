<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Pdf\Value;

use ArrudaFreitas\GovbrPades\Pdf\Value\PdfString;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \ArrudaFreitas\GovbrPades\Pdf\Value\PdfString
 */
final class PdfStringTest extends TestCase
{
    public function testALiteralKeepsItsEscapedText(): void
    {
        $string = PdfString::literal('a \\(b c\\n');

        self::assertSame('a \\(b c\\n', $string->text());
        self::assertFalse($string->isHex());
        self::assertFalse($string->isIllegible());
        self::assertNull($string->illegibleAt());
        self::assertNull($string->illegibleCharacter());
    }

    public function testALiteralMayHoldBalancedParentheses(): void
    {
        self::assertSame('a (b (c)) d', PdfString::literal('a (b (c)) d')->text());
    }

    /**
     * @dataProvider unwritableLiterals
     */
    public function testRefusesALiteralThatCouldNotBeWrittenBack(string $escapedText, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        PdfString::literal($escapedText);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function unwritableLiterals(): array
    {
        return [
            'closes before it opens' => ['a ) b ( c', 'closes before it opens, at byte 2'],
            'left open' => ['a ( b', '1 parenthesis(es) left open'],
            'dangling backslash' => ['end\\', 'dangling backslash'],
        ];
    }

    /**
     * @dataProvider rawTexts
     */
    public function testFromBytesEscapesWhatAPlainLiteralCannotHold(string $raw, string $expectedText): void
    {
        $string = PdfString::fromBytes($raw);

        self::assertSame($expectedText, $string->text());
        self::assertSame($expectedText, PdfString::literal($string->text())->text());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function rawTexts(): array
    {
        return [
            'parentheses' => ['a (b) c', 'a \\(b\\) c'],
            'closes first' => ['a ) b ( c', 'a \\) b \\( c'],
            'backslash at the end' => ['c:\\temp\\', 'c:\\\\temp\\\\'],
            'line breaks' => ["l1\r\nl2", 'l1\\r\\nl2'],
            'empty' => ['', ''],
        ];
    }

    public function testAHexadecimalStringKeepsItsDigitsAsWritten(): void
    {
        $string = PdfString::hex('0aFF');

        self::assertSame('0aFF', $string->text());
        self::assertTrue($string->isHex());
        self::assertFalse($string->isIllegible());
    }

    public function testTheEmptyHexadecimalStringIsValid(): void
    {
        self::assertSame('', PdfString::hex('')->text());
    }

    /**
     * @dataProvider invalidDigits
     */
    public function testRefusesDigitsThatAreNotNormalisedHexadecimal(string $digits, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        PdfString::hex($digits);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function invalidDigits(): array
    {
        return [
            'not hexadecimal' => ['AABBGG', 'only hold hexadecimal digits'],
            'whitespace not removed' => ['AA BB', 'only hold hexadecimal digits'],
            'odd count' => ['ABC', 'even number of digits'],
        ];
    }

    public function testAnIllegibleHexadecimalStringRemembersWhatBrokeIt(): void
    {
        $string = PdfString::illegibleHex('30G1', 42, 'G');

        self::assertSame('30G1', $string->text());
        self::assertTrue($string->isHex());
        self::assertTrue($string->isIllegible());
        self::assertSame(42, $string->illegibleAt());
        self::assertSame('G', $string->illegibleCharacter());
    }

    public function testCannotBeInstantiatedDirectly(): void
    {
        $constructor = (new ReflectionClass(PdfString::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPrivate());
    }
}
