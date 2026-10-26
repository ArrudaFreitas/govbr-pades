<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Pdf\Value;

use Countable;

final class PdfArray implements PdfValue, Countable
{
    /** @var list<PdfValue> */
    private array $items;

    /**
     * @param list<PdfValue> $items
     */
    private function __construct(array $items)
    {
        $this->items = $items;
    }

    /**
     * @param array<int, PdfValue> $items
     */
    public static function of(array $items): self
    {
        return new self(array_values($items));
    }

    /**
     * @return list<PdfValue>
     */
    public function items(): array
    {
        return $this->items;
    }

    public function get(int $index): ?PdfValue
    {
        return $this->items[$index] ?? null;
    }

    public function count(): int
    {
        return count($this->items);
    }
}
