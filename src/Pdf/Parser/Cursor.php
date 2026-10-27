<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Pdf\Parser;

final class Cursor
{
    // NUL is white space too (ISO 32000-1, table 1).
    public const WHITESPACE = '[\x00\x09\x0A\x0C\x0D\x20]';

    private string $text;

    private int $position;

    public function __construct(string $text, int $position = 0)
    {
        $this->text = $text;
        $this->position = $position;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function seek(int $position): void
    {
        $this->position = $position;
    }

    public function length(): int
    {
        return strlen($this->text);
    }

    public function atEnd(): bool
    {
        return $this->position >= strlen($this->text);
    }

    public function skipWhitespace(): void
    {
        while (true) {
            if (preg_match('/\G' . self::WHITESPACE . '*/', $this->text, $match, 0, $this->position) === 1) {
                $this->position += strlen($match[0]);
            }

            if (($this->text[$this->position] ?? '') !== '%') {
                return;
            }

            $this->position += strcspn($this->text, "\r\n", $this->position);
        }
    }

    public function peek(int $length = 1): string
    {
        return substr($this->text, $this->position, $length);
    }

    public function peekAt(int $offset, int $length): string
    {
        return substr($this->text, $this->position + $offset, $length);
    }

    public function advance(int $length): void
    {
        $this->position += $length;
    }

    public function consumeIf(string $word): bool
    {
        if (substr($this->text, $this->position, strlen($word)) !== $word) {
            return false;
        }

        $this->position += strlen($word);

        return true;
    }

    /**
     * @return array<int, string>|null
     */
    public function matchHere(string $pattern): ?array
    {
        if (preg_match('~\G' . $pattern . '~', $this->text, $match, 0, $this->position) !== 1) {
            return null;
        }

        $this->position += strlen($match[0]);

        return $match;
    }

    public function find(string $word): ?int
    {
        $found = strpos($this->text, $word, $this->position);

        return $found === false ? null : $found;
    }

    public function slice(int $from, int $length): string
    {
        return substr($this->text, $from, $length);
    }

    public function sample(int $length = 12): string
    {
        return (string) preg_replace('/[\x00-\x1F]/', '.', substr($this->text, $this->position, $length));
    }
}
