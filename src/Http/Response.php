<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Http;

final class Response
{
    private int $statusCode;

    /** @var array<string, string> */
    private array $headers;

    private string $rawBody;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(int $statusCode, array $headers, string $rawBody)
    {
        $this->statusCode = $statusCode;
        $this->headers = [];
        $this->rawBody = $rawBody;

        foreach ($headers as $name => $value) {
            $this->headers[strtolower($name)] = $value;
        }
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Returned exactly as it arrived, byte for byte. assinarPKCS7 answers with binary
     * DER: trimming it, or passing it through any encoding or mb_* conversion, breaks
     * the signature. There is deliberately no accessor that interprets the body.
     */
    public function rawBody(): string
    {
        return $this->rawBody;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
