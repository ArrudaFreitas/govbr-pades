<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Pdf\Value;

final class PdfInteger implements PdfNumber
{
    private int $value;

    private function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function of(int $value): self
    {
        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function asFloat(): float
    {
        return (float) $this->value;
    }
}
