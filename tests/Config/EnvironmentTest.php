<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Config;

use ArrudaFreitas\GovbrPades\Config\Environment;
use ArrudaFreitas\GovbrPades\Exceptions\InvalidConfigurationException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \ArrudaFreitas\GovbrPades\Config\Environment
 *
 * @uses \ArrudaFreitas\GovbrPades\Exceptions\InvalidConfigurationException
 */
final class EnvironmentTest extends TestCase
{
    public function testStagingBuildsTheDocumentedOauthEndpoints(): void
    {
        $environment = Environment::staging();

        self::assertSame(
            'https://cas.staging.iti.br/oauth2.0/authorize',
            $environment->oauthEndpoint('authorize')
        );
        self::assertSame(
            'https://cas.staging.iti.br/oauth2.0/token',
            $environment->oauthEndpoint('token')
        );
    }

    public function testStagingBuildsTheDocumentedApiEndpoints(): void
    {
        $environment = Environment::staging();

        self::assertSame(
            'https://assinatura-api.staging.iti.br/externo/v2/certificadoPublico',
            $environment->apiEndpoint('certificadoPublico')
        );
        self::assertSame(
            'https://assinatura-api.staging.iti.br/externo/v2/assinarPKCS7',
            $environment->apiEndpoint('assinarPKCS7')
        );
    }

    public function testProductionKeepsThePathsAndOnlySwapsTheHost(): void
    {
        $environment = Environment::production();

        self::assertSame(
            'https://cas.iti.br/oauth2.0/token',
            $environment->oauthEndpoint('token')
        );
        self::assertSame(
            'https://assinatura-api.iti.br/externo/v2/assinarPKCS7',
            $environment->apiEndpoint('assinarPKCS7')
        );
    }

    /**
     * @dataProvider slashCombinations
     */
    public function testEndpointsNeverProduceDoubleSlashes(string $base, string $path): void
    {
        $environment = Environment::custom($base, $base);

        self::assertSame('https://example.test/oauth/authorize', $environment->oauthEndpoint($path));
        self::assertSame('https://example.test/oauth/authorize', $environment->apiEndpoint($path));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function slashCombinations(): array
    {
        return [
            'no slash' => ['https://example.test/oauth', 'authorize'],
            'trailing slash on base' => ['https://example.test/oauth/', 'authorize'],
            'leading slash on path' => ['https://example.test/oauth', '/authorize'],
            'slash on both sides' => ['https://example.test/oauth/', '/authorize'],
        ];
    }

    /**
     * @dataProvider secureOrLocalBases
     */
    public function testACustomBaseOnHttpsOrOnTheLocalMachineIsAccepted(string $base): void
    {
        $environment = Environment::custom($base, $base);

        self::assertSame($base . '/token', $environment->oauthEndpoint('token'));
    }

    /**
     * @return array<string, array{string}>
     */
    public function secureOrLocalBases(): array
    {
        return [
            'https' => ['https://example.test/oauth'],
            'https in capitals' => ['HTTPS://example.test/oauth'],
            'http on localhost' => ['http://localhost:8080/oauth'],
            'http on 127.0.0.1' => ['http://127.0.0.1:8080/oauth'],
            'http on ::1' => ['http://[::1]:8080/oauth'],
        ];
    }

    /**
     * @dataProvider insecureBases
     */
    public function testPlainHttpOffTheLocalMachineIsRefused(string $base): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('must use https');

        Environment::custom($base, 'https://example.test/api');
    }

    /**
     * @return array<string, array{string}>
     */
    public function insecureBases(): array
    {
        return [
            'http elsewhere' => ['http://example.test/oauth'],
            'localhost as user info' => ['http://localhost@attacker.test/oauth'],
            'loopback as a subdomain' => ['http://127.0.0.1.attacker.test/oauth'],
            'another scheme' => ['ftp://example.test/oauth'],
        ];
    }

    /**
     * @dataProvider notUrls
     */
    public function testABaseThatIsNotAUrlIsRefused(string $base): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('must be a valid URL');

        Environment::custom($base, 'https://example.test/api');
    }

    /**
     * @return array<string, array{string}>
     */
    public function notUrls(): array
    {
        return [
            'plain text' => ['blabla'],
            'no host' => ['https:///oauth'],
            'local file' => ['file:///etc/passwd'],
        ];
    }

    public function testTheApiBaseIsCheckedToo(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('api base URL');

        Environment::custom('https://example.test/oauth', 'http://example.test/api');
    }

    public function testCannotBeInstantiatedDirectly(): void
    {
        $constructor = (new ReflectionClass(Environment::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPrivate());
    }
}
