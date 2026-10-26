<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Pdf\Value;

use ArrudaFreitas\GovbrPades\Pdf\Value\PdfName;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \ArrudaFreitas\GovbrPades\Pdf\Value\PdfName
 */
final class PdfNameTest extends TestCase
{
    public function testHoldsTheDecodedBytes(): void
    {
        self::assertSame('A B', PdfName::of('A B')->value());
        self::assertSame("caf\xC3\xA9", PdfName::of("caf\xC3\xA9")->value());
    }

    public function testTheEmptyNameIsValid(): void
    {
        self::assertSame('', PdfName::of('')->value());
    }

    public function testEqualityIsByValue(): void
    {
        self::assertTrue(PdfName::of('Type')->equals(PdfName::of('Type')));
        self::assertFalse(PdfName::of('Type')->equals(PdfName::of('type')));
    }

    public function testCannotBeInstantiatedDirectly(): void
    {
        $constructor = (new ReflectionClass(PdfName::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPrivate());
    }
}
