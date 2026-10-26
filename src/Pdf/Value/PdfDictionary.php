<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Pdf\Value;

use Countable;

final class PdfDictionary implements PdfValue, Countable
{
    /** @var array<array-key, PdfValue> */
    private array $entries;

    /** @var array<string, int> */
    private array $repeatedKeys;

    /**
     * @param array<array-key, PdfValue> $entries
     * @param array<string, int> $repeatedKeys
     */
    private function __construct(array $entries, array $repeatedKeys)
    {
        $this->entries = $entries;
        $this->repeatedKeys = $repeatedKeys;
    }

    /**
     * @param array<array-key, PdfValue> $entries
     */
    public static function of(array $entries): self
    {
        return new self($entries, []);
    }

    /**
     * @param array<array-key, PdfValue> $entries
     * @param array<string, int> $repeatedKeys
     */
    public static function withRepeatedKeys(array $entries, array $repeatedKeys): self
    {
        return new self($entries, $repeatedKeys);
    }

    public function get(string $key): ?PdfValue
    {
        return $this->entries[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->entries);
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_map('strval', array_keys($this->entries));
    }

    /**
     * @return array<string, int>
     */
    public function repeatedKeys(): array
    {
        return $this->repeatedKeys;
    }

    public function with(string $key, PdfValue $value): self
    {
        $entries = $this->entries;
        $entries[$key] = $value;

        return new self($entries, $this->repeatedKeys);
    }

    public function count(): int
    {
        return count($this->entries);
    }
}
