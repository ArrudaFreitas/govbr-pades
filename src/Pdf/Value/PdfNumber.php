<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Pdf\Value;

interface PdfNumber extends PdfValue
{
    public function asFloat(): float;
}
