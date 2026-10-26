<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Pdf\Value;

use ArrudaFreitas\GovbrPades\Pdf\Value\PdfArray;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfInteger;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \ArrudaFreitas\GovbrPades\Pdf\Value\PdfArray
 *
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Value\PdfInteger
 */
final class PdfArrayTest extends TestCase
{
    public function testKeepsTheItemsInOrder(): void
    {
        $first = PdfInteger::of(0);
        $second = PdfInteger::of(595);
        $array = PdfArray::of([$first, $second]);

        self::assertSame([$first, $second], $array->items());
        self::assertSame($second, $array->get(1));
        self::assertCount(2, $array);
    }

    public function testRenumbersTheItemsFromZero(): void
    {
        $item = PdfInteger::of(1);

        self::assertSame([0 => $item], PdfArray::of([7 => $item])->items());
    }

    public function testAMissingIndexIsNull(): void
    {
        self::assertNull(PdfArray::of([])->get(0));
    }

    public function testCannotBeInstantiatedDirectly(): void
    {
        $constructor = (new ReflectionClass(PdfArray::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPrivate());
    }
}
