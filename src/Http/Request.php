<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Http;

use ArrudaFreitas\GovbrPades\Exceptions\InvalidPayloadException;

final class Request
{
    private string $method;
    private string $url;

    /** @var array<string, string> */
    private array $headers;

    private ?string $body;

    /**
     * @param array<string, string> $headers
     */
    private function __construct(string $method, string $url, array $headers, ?string $body)
    {
        $this->method = $method;
        $this->url = $url;
        $this->headers = [];
        $this->body = $body;

        foreach ($headers as $name => $value) {
            $this->headers[strtolower($name)] = $value;
        }
    }

    public static function get(string $url): self
    {
        return new self('GET', $url, [], null);
    }

    /**
     * @param array<string, string> $fields
     */
    public static function postForm(string $url, array $fields): self
    {
        $headers = ['content-type' => 'application/x-www-form-urlencoded'];

        return new self('POST', $url, $headers, http_build_query($fields));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @throws InvalidPayloadException
     */
    public static function postJson(string $url, array $payload): self
    {
        $body = json_encode($payload);

        if ($body === false) {
            throw InvalidPayloadException::notEncodable(json_last_error_msg());
        }

        $headers = ['content-type' => 'application/json'];

        return new self('POST', $url, $headers, $body);
    }

    public function withHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        $headers[strtolower($name)] = $value;

        return new self($this->method, $this->url, $headers, $this->body);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function url(): string
    {
        return $this->url;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): ?string
    {
        return $this->body;
    }
}
