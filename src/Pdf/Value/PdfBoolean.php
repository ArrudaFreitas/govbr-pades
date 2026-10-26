<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Pdf\Value;

final class PdfBoolean implements PdfValue
{
    private bool $value;

    private function __construct(bool $value)
    {
        $this->value = $value;
    }

    public static function of(bool $value): self
    {
        return new self($value);
    }

    public function value(): bool
    {
        return $this->value;
    }
}
