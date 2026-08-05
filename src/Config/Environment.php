<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Config;

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
}
