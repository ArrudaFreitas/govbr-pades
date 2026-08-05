<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Config;

use ArrudaFreitas\GovbrPades\Config\ClientSecret;
use ArrudaFreitas\GovbrPades\Exceptions\GovbrPadesException;
use ArrudaFreitas\GovbrPades\Exceptions\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \ArrudaFreitas\GovbrPades\Config\ClientSecret
 *
 * @uses \ArrudaFreitas\GovbrPades\Exceptions\InvalidConfigurationException
 */
final class ClientSecretTest extends TestCase
{
    private const SECRET = 'p4ssw0rd-that-must-not-leak';

    public function testRevealReturnsTheOriginalValue(): void
    {
        self::assertSame(self::SECRET, ClientSecret::fromString(self::SECRET)->reveal());
    }

    public function testCastingToStringMasksTheValue(): void
    {
        $secret = ClientSecret::fromString(self::SECRET);

        self::assertSame('***', (string) $secret);
    }

    public function testDebugInfoMasksTheValue(): void
    {
        $secret = ClientSecret::fromString(self::SECRET);

        self::assertSame(['value' => '***'], $secret->__debugInfo());
        self::assertStringNotContainsString(self::SECRET, print_r($secret, true));
    }

    /**
     * @dataProvider blankValues
     */
    public function testRejectsBlankValues(string $value): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('client_secret');

        ClientSecret::fromString($value);
    }

    /**
     * @return array<string, array{string}>
     */
    public function blankValues(): array
    {
        return [
            'empty' => [''],
            'spaces' => ['   '],
            'tab and newline' => ["\t\n"],
        ];
    }

    public function testRejectionIsCatchableThroughTheMarkerInterface(): void
    {
        $this->expectException(GovbrPadesException::class);

        ClientSecret::fromString('');
    }

    public function testCannotBeInstantiatedDirectly(): void
    {
        $constructor = (new ReflectionClass(ClientSecret::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPrivate());
    }
}
