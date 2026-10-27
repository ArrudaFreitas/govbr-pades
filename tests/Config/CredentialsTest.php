<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Config;

use ArrudaFreitas\GovbrPades\Config\ClientSecret;
use ArrudaFreitas\GovbrPades\Config\Credentials;
use ArrudaFreitas\GovbrPades\Exceptions\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Throwable;

/**
 * @covers \ArrudaFreitas\GovbrPades\Config\Credentials
 *
 * @uses \ArrudaFreitas\GovbrPades\Config\ClientSecret
 * @uses \ArrudaFreitas\GovbrPades\Exceptions\InvalidConfigurationException
 */
final class CredentialsTest extends TestCase
{
    private const SECRET = 'p4ssw0rd-that-must-not-leak';
    private const REDIRECT_URI = 'https://www.nomeorgao.gov.br/assinar';

    public function testCreateExposesWhatItReceived(): void
    {
        $secret = ClientSecret::fromString(self::SECRET);

        $credentials = Credentials::create('client-id', $secret, self::REDIRECT_URI);

        self::assertSame('client-id', $credentials->clientId());
        self::assertSame(self::REDIRECT_URI, $credentials->redirectUri());
        self::assertSame($secret, $credentials->clientSecret());
    }

    /**
     * @dataProvider blankClientIds
     */
    public function testRejectsBlankClientId(string $clientId): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('client_id');

        Credentials::create($clientId, ClientSecret::fromString(self::SECRET), self::REDIRECT_URI);
    }

    /**
     * @return array<string, array{string}>
     */
    public function blankClientIds(): array
    {
        return [
            'empty' => [''],
            'spaces' => ['   '],
        ];
    }

    /**
     * @dataProvider invalidRedirectUris
     */
    public function testRejectsInvalidRedirectUri(string $redirectUri): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('redirect_uri');

        Credentials::create('client-id', ClientSecret::fromString(self::SECRET), $redirectUri);
    }

    /**
     * @return array<string, array{string}>
     */
    public function invalidRedirectUris(): array
    {
        return [
            'empty' => [''],
            'missing scheme' => ['www.nomeorgao.gov.br/assinar'],
            'protocol relative' => ['//www.nomeorgao.gov.br/assinar'],
        ];
    }

    public function testTheSecretDoesNotLeakThroughTheTraceOfAFailedCreation(): void
    {
        $secret = ClientSecret::fromString(self::SECRET);
        ini_set('zend.exception_ignore_args', '0');

        try {
            Credentials::create('client-id', $secret, 'not-a-url');
            self::fail('Expected InvalidConfigurationException was not thrown.');
        } catch (InvalidConfigurationException $caught) {
            self::assertStringNotContainsString(self::SECRET, $caught->getTraceAsString());
            self::assertStringNotContainsString(self::SECRET, (string) $caught);

            $arguments = $this->argumentsOfFailedCreation($caught);
            self::assertCount(3, $arguments);

            foreach ($arguments as $argument) {
                self::assertStringNotContainsString(self::SECRET, print_r($argument, true));
            }
        } finally {
            ini_restore('zend.exception_ignore_args');
        }
    }

    /**
     * The whole trace cannot be dumped here: under PHPUnit it holds the test suite
     * object graph and print_r() exhausts memory. Only the create() frame matters.
     *
     * @return array<int, mixed>
     */
    private function argumentsOfFailedCreation(Throwable $caught): array
    {
        foreach ($caught->getTrace() as $frame) {
            if (($frame['class'] ?? null) !== Credentials::class) {
                continue;
            }

            if (($frame['function'] ?? null) !== 'create') {
                continue;
            }

            $arguments = $frame['args'] ?? [];

            return is_array($arguments) ? array_values($arguments) : [];
        }

        return [];
    }

    public function testCannotBeInstantiatedDirectly(): void
    {
        $constructor = (new ReflectionClass(Credentials::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPrivate());
    }
}
