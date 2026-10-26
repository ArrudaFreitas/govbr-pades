<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Pdf\Exceptions;

use ArrudaFreitas\GovbrPades\Exceptions\GovbrPadesException;
use RuntimeException;

final class InvalidPdfException extends RuntimeException implements GovbrPadesException
{
    public static function unrecognisedValue(int $offset, string $sample): self
    {
        return new self(sprintf("Unrecognised value at byte %d: '%s'.", $offset, $sample));
    }

    public static function invalidName(int $offset): self
    {
        return new self(sprintf('Invalid name at byte %d.', $offset));
    }

    public static function unterminatedDictionary(): self
    {
        return new self('Dictionary does not close with ">>" before the text ends.');
    }

    public static function unterminatedArray(): self
    {
        return new self('Array does not close with "]" before the text ends.');
    }

    public static function unterminatedLiteralString(int $offset, int $openParentheses): self
    {
        return new self(sprintf(
            'Literal string starting at byte %d does not close (%d parenthesis(es) still open).',
            $offset,
            $openParentheses
        ));
    }

    public static function danglingEscape(int $offset): self
    {
        return new self(sprintf('Escape "\\" at the end of the text, byte %d.', $offset));
    }

    public static function unterminatedHexString(int $offset): self
    {
        return new self(sprintf("Hexadecimal string starting at byte %d does not close with '>'.", $offset));
    }

    public static function nonHexadecimalDigit(int $offset, string $character): self
    {
        return new self(sprintf(
            "Hexadecimal string starting at byte %d has a non-hexadecimal character '%s'.",
            $offset,
            $character
        ));
    }
}
