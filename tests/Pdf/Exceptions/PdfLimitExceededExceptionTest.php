<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Pdf\Exceptions;

use ArrudaFreitas\GovbrPades\Pdf\Exceptions\PdfLimitExceededException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ArrudaFreitas\GovbrPades\Pdf\Exceptions\PdfLimitExceededException
 */
final class PdfLimitExceededExceptionTest extends TestCase
{
    public function testNestingTooDeepNamesTheLimitAndThePlace(): void
    {
        self::assertSame(
            'Value nesting goes beyond 256 levels at byte 1024; refused before it overflows the PHP stack.',
            PdfLimitExceededException::nestingTooDeep(256, 1024)->getMessage()
        );
    }
}
