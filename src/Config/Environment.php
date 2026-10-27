<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Config;

use ArrudaFreitas\GovbrPades\Exceptions\InvalidConfigurationException;

final class Environment
{
    private string $oauthBase;
    private string $apiBase;

    private function __construct(string $oauthBase, string $apiBase)
    {
        $this->oauthBase = rtrim($oauthBase, '/');
        $this->apiBase = rtrim($apiBase, '/');
    }

    public static function staging(): self
    {
        return new self(
            'https://cas.staging.iti.br/oauth2.0',
            'https://assinatura-api.staging.iti.br/externo/v2'
        );
    }

    public static function production(): self
    {
        return new self(
            'https://cas.iti.br/oauth2.0',
            'https://assinatura-api.iti.br/externo/v2'
        );
    }

    public static function custom(string $oauthBase, string $apiBase): self
    {
        self::requireTls($oauthBase, 'oauth base URL');
        self::requireTls($apiBase, 'api base URL');

        return new self($oauthBase, $apiBase);
    }

    public function oauthEndpoint(string $path): string
    {
        return $this->oauthBase . '/' . ltrim($path, '/');
    }

    public function apiEndpoint(string $path): string
    {
        return $this->apiBase . '/' . ltrim($path, '/');
    }

    private static function requireTls(string $url, string $field): void
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($scheme === '' || $host === '') {
            throw InvalidConfigurationException::invalidUrl($field);
        }

        if ($scheme === 'https') {
            return;
        }

        // The client secret is sent to these hosts, so plain http would expose it on the
        // network: it is accepted only for the local machine, where test servers run.
        if ($scheme === 'http' && in_array($host, ['localhost', '127.0.0.1', '[::1]'], true)) {
            return;
        }

        throw InvalidConfigurationException::insecureUrl($field);
    }
}
