<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Pdf\Value;

final class PdfReference implements PdfValue
{
    private int $number;

    private int $generation;

    private function __construct(int $number, int $generation)
    {
        $this->number = $number;
        $this->generation = $generation;
    }

    public static function to(int $number, int $generation = 0): self
    {
        return new self($number, $generation);
    }

    public function number(): int
    {
        return $this->number;
    }

    public function generation(): int
    {
        return $this->generation;
    }
}
