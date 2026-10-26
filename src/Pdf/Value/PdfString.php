<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Pdf\Value;

use InvalidArgumentException;

final class PdfString implements PdfValue
{
    private string $text;

    private bool $hex;

    private ?int $illegibleAt;

    private ?string $illegibleCharacter;

    private function __construct(string $text, bool $hex, ?int $illegibleAt, ?string $illegibleCharacter)
    {
        $this->text = $text;
        $this->hex = $hex;
        $this->illegibleAt = $illegibleAt;
        $this->illegibleCharacter = $illegibleCharacter;
    }

    public static function literal(string $escapedText): self
    {
        $depth = 0;
        $length = strlen($escapedText);

        for ($i = 0; $i < $length; $i++) {
            $byte = $escapedText[$i];

            if ($byte === '\\') {
                if ($i + 1 >= $length) {
                    throw new InvalidArgumentException(
                        'A literal string cannot end in a dangling backslash: it would swallow the closing ")".'
                    );
                }

                $i++;
                continue;
            }

            if ($byte === '(') {
                $depth++;
                continue;
            }

            if ($byte === ')') {
                $depth--;

                if ($depth < 0) {
                    throw new InvalidArgumentException(sprintf(
                        'A literal string has a ")" that closes before it opens, at byte %d.',
                        $i
                    ));
                }
            }
        }

        if ($depth !== 0) {
            throw new InvalidArgumentException(sprintf(
                'A literal string has %d parenthesis(es) left open.',
                $depth
            ));
        }

        return new self($escapedText, false, null, null);
    }

    public static function fromBytes(string $bytes): self
    {
        return new self(strtr($bytes, [
            '\\' => '\\\\',
            '(' => '\\(',
            ')' => '\\)',
            "\r" => '\\r',
            "\n" => '\\n',
        ]), false, null, null);
    }

    public static function hex(string $digits): self
    {
        if ($digits !== '' && !ctype_xdigit($digits)) {
            throw new InvalidArgumentException('A hexadecimal string can only hold hexadecimal digits.');
        }

        if (strlen($digits) % 2 === 1) {
            throw new InvalidArgumentException('A normalised hexadecimal string has an even number of digits.');
        }

        return new self($digits, true, null, null);
    }

    public static function illegibleHex(string $digits, int $startsAt, string $character): self
    {
        return new self($digits, true, $startsAt, $character);
    }

    public function text(): string
    {
        return $this->text;
    }

    public function isHex(): bool
    {
        return $this->hex;
    }

    public function isIllegible(): bool
    {
        return $this->illegibleAt !== null;
    }

    public function illegibleAt(): ?int
    {
        return $this->illegibleAt;
    }

    public function illegibleCharacter(): ?string
    {
        return $this->illegibleCharacter;
    }
}
