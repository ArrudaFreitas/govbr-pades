<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Pdf\Exceptions;

use ArrudaFreitas\GovbrPades\Exceptions\GovbrPadesException;
use ArrudaFreitas\GovbrPades\Pdf\Exceptions\InvalidPdfException;
use ArrudaFreitas\GovbrPades\Pdf\Exceptions\PdfLimitExceededException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

/**
 * @coversNothing
 */
final class PdfExceptionCatalogTest extends TestCase
{
    /**
     * @dataProvider catalog
     *
     * @param class-string $exceptionClass
     * @param class-string $expectedSplParent
     */
    public function testEveryExceptionIsCatchableAndSemanticallyPlaced(
        string $exceptionClass,
        string $expectedSplParent
    ): void {
        $reflection = new ReflectionClass($exceptionClass);

        self::assertTrue(
            $reflection->implementsInterface(GovbrPadesException::class),
            $exceptionClass . ' must be catchable through GovbrPadesException.'
        );
        self::assertTrue(
            $reflection->isSubclassOf($expectedSplParent),
            $exceptionClass . ' must extend ' . $expectedSplParent . '.'
        );
        self::assertTrue($reflection->isFinal(), $exceptionClass . ' must be final.');
    }

    /**
     * @return array<string, array{class-string, class-string}>
     */
    public function catalog(): array
    {
        return [
            'invalid pdf' => [InvalidPdfException::class, RuntimeException::class],
            'pdf limit exceeded' => [PdfLimitExceededException::class, RuntimeException::class],
        ];
    }
}
