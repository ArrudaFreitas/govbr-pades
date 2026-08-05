<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Config;

use ArrudaFreitas\GovbrPades\Exceptions\InvalidConfigurationException;

final class Credentials
{
    private string $clientId;
    private ClientSecret $clientSecret;
    private string $redirectUri;

    private function __construct(string $clientId, ClientSecret $clientSecret, string $redirectUri)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
    }

    public static function create(string $clientId, ClientSecret $clientSecret, string $redirectUri): self
    {
        if (trim($clientId) === '') {
            throw InvalidConfigurationException::emptyValue('client_id');
        }

        if (filter_var($redirectUri, FILTER_VALIDATE_URL) === false) {
            throw InvalidConfigurationException::invalidUrl('redirect_uri');
        }

        return new self($clientId, $clientSecret, $redirectUri);
    }

    public function clientId(): string
    {
        return $this->clientId;
    }

    public function clientSecret(): ClientSecret
    {
        return $this->clientSecret;
    }

    public function redirectUri(): string
    {
        return $this->redirectUri;
    }
}
