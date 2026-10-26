<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Pdf\Value;

use ArrudaFreitas\GovbrPades\Pdf\Value\PdfNumber;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfReal;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \ArrudaFreitas\GovbrPades\Pdf\Value\PdfReal
 */
final class PdfRealTest extends TestCase
{
    public function testKeepsTheValue(): void
    {
        self::assertSame(841.89, PdfReal::of(841.89)->value());
    }

    public function testKeepsTheSignOfNegativeZero(): void
    {
        self::assertSame('-0', (string) PdfReal::of(-0.0)->value());
    }

    public function testIsANumber(): void
    {
        $real = PdfReal::of(0.5);

        self::assertInstanceOf(PdfNumber::class, $real);
        self::assertSame(0.5, $real->asFloat());
    }

    public function testCannotBeInstantiatedDirectly(): void
    {
        $constructor = (new ReflectionClass(PdfReal::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPrivate());
    }
}
