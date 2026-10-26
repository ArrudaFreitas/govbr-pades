<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Pdf\Value;

final class PdfName implements PdfValue
{
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function of(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
