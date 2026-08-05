<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Exceptions;

use ArrudaFreitas\GovbrPades\Exceptions\GovbrPadesException;
use ArrudaFreitas\GovbrPades\Exceptions\InvalidConfigurationException;
use ArrudaFreitas\GovbrPades\Exceptions\InvalidPayloadException;
use ArrudaFreitas\GovbrPades\Exceptions\TransportFailureException;
use ArrudaFreitas\GovbrPades\Exceptions\UnexpectedResponseException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

/**
 * The project rule, enforced instead of merely written down: every exception the library
 * throws is catchable through the marker interface, and still extends the SPL exception
 * that fits it semantically, so existing catch blocks keep working.
 *
 * @coversNothing
 */
final class ExceptionCatalogTest extends TestCase
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
            'invalid configuration' => [InvalidConfigurationException::class, InvalidArgumentException::class],
            'invalid payload' => [InvalidPayloadException::class, InvalidArgumentException::class],
            'transport failure' => [TransportFailureException::class, RuntimeException::class],
            'unexpected response' => [UnexpectedResponseException::class, RuntimeException::class],
        ];
    }

    /**
     * Layer 1 establishes the contract, not the catalogue. "Bronze account" and
     * "irregular CPF" belong to layers 2 and 4, which know which endpoint they called.
     */
    public function testNoBusinessErrorLeakedIntoLayerOne(): void
    {
        $files = glob(__DIR__ . '/../../src/Exceptions/*.php');

        self::assertIsArray($files);

        $names = array_map(
            static function (string $path): string {
                return basename($path, '.php');
            },
            $files
        );

        sort($names);

        self::assertSame(
            [
                'GovbrPadesException',
                'InvalidConfigurationException',
                'InvalidPayloadException',
                'TransportFailureException',
                'UnexpectedResponseException',
            ],
            $names
        );
    }
}
