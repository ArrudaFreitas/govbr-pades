<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Pdf\Exceptions;

use ArrudaFreitas\GovbrPades\Exceptions\GovbrPadesException;
use RuntimeException;

final class PdfLimitExceededException extends RuntimeException implements GovbrPadesException
{
    public static function nestingTooDeep(int $maximumDepth, int $offset): self
    {
        return new self(sprintf(
            'Value nesting goes beyond %d levels at byte %d; refused before it overflows the PHP stack.',
            $maximumDepth,
            $offset
        ));
    }
}
