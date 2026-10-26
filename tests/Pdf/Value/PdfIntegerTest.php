<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Pdf\Value;

use ArrudaFreitas\GovbrPades\Pdf\Value\PdfInteger;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfNumber;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \ArrudaFreitas\GovbrPades\Pdf\Value\PdfInteger
 */
final class PdfIntegerTest extends TestCase
{
    public function testKeepsTheExactValue(): void
    {
        self::assertSame(PHP_INT_MAX, PdfInteger::of(PHP_INT_MAX)->value());
        self::assertSame(-42, PdfInteger::of(-42)->value());
    }

    public function testIsANumberThatCanBeUsedAsAFloat(): void
    {
        $integer = PdfInteger::of(595);

        self::assertInstanceOf(PdfNumber::class, $integer);
        self::assertSame(595.0, $integer->asFloat());
    }

    public function testCannotBeInstantiatedDirectly(): void
    {
        $constructor = (new ReflectionClass(PdfInteger::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPrivate());
    }
}
