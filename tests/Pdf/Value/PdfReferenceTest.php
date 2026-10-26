<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Pdf\Value;

use ArrudaFreitas\GovbrPades\Pdf\Value\PdfReference;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \ArrudaFreitas\GovbrPades\Pdf\Value\PdfReference
 */
final class PdfReferenceTest extends TestCase
{
    public function testGenerationDefaultsToZero(): void
    {
        $reference = PdfReference::to(5);

        self::assertSame(5, $reference->number());
        self::assertSame(0, $reference->generation());
    }

    public function testKeepsAnExplicitGeneration(): void
    {
        self::assertSame(7, PdfReference::to(3, 7)->generation());
    }

    public function testCannotBeInstantiatedDirectly(): void
    {
        $constructor = (new ReflectionClass(PdfReference::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPrivate());
    }
}
