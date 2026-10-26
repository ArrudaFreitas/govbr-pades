<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Pdf\Value;

final class PdfReal implements PdfNumber
{
    private float $value;

    private function __construct(float $value)
    {
        $this->value = $value;
    }

    public static function of(float $value): self
    {
        return new self($value);
    }

    public function value(): float
    {
        return $this->value;
    }

    public function asFloat(): float
    {
        return $this->value;
    }
}
