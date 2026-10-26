<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Pdf\Value;

use ArrudaFreitas\GovbrPades\Pdf\Value\PdfDictionary;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfInteger;
use ArrudaFreitas\GovbrPades\Pdf\Value\PdfName;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \ArrudaFreitas\GovbrPades\Pdf\Value\PdfDictionary
 *
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Value\PdfInteger
 * @uses \ArrudaFreitas\GovbrPades\Pdf\Value\PdfName
 */
final class PdfDictionaryTest extends TestCase
{
    public function testFindsAnEntryByKey(): void
    {
        $page = PdfName::of('Page');
        $dictionary = PdfDictionary::of(['Type' => $page]);

        self::assertSame($page, $dictionary->get('Type'));
        self::assertTrue($dictionary->has('Type'));
        self::assertNull($dictionary->get('Kids'));
        self::assertFalse($dictionary->has('Kids'));
        self::assertCount(1, $dictionary);
    }

    public function testKeepsTheOrderOfArrival(): void
    {
        $dictionary = PdfDictionary::of(['Type' => PdfName::of('Page'), 'Count' => PdfInteger::of(1)]);

        self::assertSame(['Type', 'Count'], $dictionary->keys());
    }

    public function testANumericKeyStaysAString(): void
    {
        $one = PdfInteger::of(1);
        $dictionary = PdfDictionary::of(['123' => $one]);

        self::assertSame(['123'], $dictionary->keys());
        self::assertSame($one, $dictionary->get('123'));
    }

    public function testADictionaryWithoutRepetitionReportsNone(): void
    {
        self::assertSame([], PdfDictionary::of(['Type' => PdfName::of('Page')])->repeatedKeys());
    }

    public function testRemembersWhichKeysWereRepeated(): void
    {
        $dictionary = PdfDictionary::withRepeatedKeys(['Type' => PdfName::of('Page')], ['Type' => 2]);

        self::assertSame(['Type' => 2], $dictionary->repeatedKeys());
    }

    public function testWithReturnsANewDictionaryAndLeavesTheOriginalAlone(): void
    {
        $original = PdfDictionary::withRepeatedKeys(['Type' => PdfName::of('Page')], ['Type' => 2]);
        $changed = $original->with('Count', PdfInteger::of(1));

        self::assertSame(['Type'], $original->keys());
        self::assertSame(['Type', 'Count'], $changed->keys());
        self::assertSame(['Type' => 2], $changed->repeatedKeys());
    }

    public function testWithReplacesAnExistingKeyInPlace(): void
    {
        $two = PdfInteger::of(2);
        $dictionary = PdfDictionary::of(['A' => PdfInteger::of(1), 'B' => PdfInteger::of(1)])->with('A', $two);

        self::assertSame(['A', 'B'], $dictionary->keys());
        self::assertSame($two, $dictionary->get('A'));
    }

    public function testCannotBeInstantiatedDirectly(): void
    {
        $constructor = (new ReflectionClass(PdfDictionary::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPrivate());
    }
}
