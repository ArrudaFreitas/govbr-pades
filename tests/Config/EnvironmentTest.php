<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Config;

use ArrudaFreitas\GovbrPades\Config\Environment;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \ArrudaFreitas\GovbrPades\Config\Environment
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

    public function testCannotBeInstantiatedDirectly(): void
    {
        $constructor = (new ReflectionClass(Environment::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPrivate());
    }
}
