<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Pdf\Exceptions;

use ArrudaFreitas\GovbrPades\Pdf\Exceptions\InvalidPdfException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ArrudaFreitas\GovbrPades\Pdf\Exceptions\InvalidPdfException
 */
final class InvalidPdfExceptionTest extends TestCase
{
    /**
     * @dataProvider factories
     *
     * @param callable(): InvalidPdfException $factory
     */
    public function testEveryFactoryTellsWhereTheRefusalHappened(callable $factory, string $expectedMessage): void
    {
        self::assertSame($expectedMessage, $factory()->getMessage());
    }

    /**
     * @return array<string, array{callable(): InvalidPdfException, string}>
     */
    public function factories(): array
    {
        return [
            'unrecognised value' => [
                static function (): InvalidPdfException {
                    return InvalidPdfException::unrecognisedValue(12, ')..x');
                },
                "Unrecognised value at byte 12: ')..x'.",
            ],
            'invalid name' => [
                static function (): InvalidPdfException {
                    return InvalidPdfException::invalidName(7);
                },
                'Invalid name at byte 7.',
            ],
            'unterminated dictionary' => [
                static function (): InvalidPdfException {
                    return InvalidPdfException::unterminatedDictionary();
                },
                'Dictionary does not close with ">>" before the text ends.',
            ],
            'unterminated array' => [
                static function (): InvalidPdfException {
                    return InvalidPdfException::unterminatedArray();
                },
                'Array does not close with "]" before the text ends.',
            ],
            'unterminated literal string' => [
                static function (): InvalidPdfException {
                    return InvalidPdfException::unterminatedLiteralString(3, 2);
                },
                'Literal string starting at byte 3 does not close (2 parenthesis(es) still open).',
            ],
            'dangling escape' => [
                static function (): InvalidPdfException {
                    return InvalidPdfException::danglingEscape(9);
                },
                'Escape "\\" at the end of the text, byte 9.',
            ],
            'unterminated hexadecimal string' => [
                static function (): InvalidPdfException {
                    return InvalidPdfException::unterminatedHexString(4);
                },
                "Hexadecimal string starting at byte 4 does not close with '>'.",
            ],
            'non-hexadecimal digit' => [
                static function (): InvalidPdfException {
                    return InvalidPdfException::nonHexadecimalDigit(30, 'G');
                },
                "Hexadecimal string starting at byte 30 has a non-hexadecimal character 'G'.",
            ],
        ];
    }
}
