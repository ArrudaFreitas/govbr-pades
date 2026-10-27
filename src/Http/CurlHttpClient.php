<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Http;

use ArrudaFreitas\GovbrPades\Exceptions\InvalidConfigurationException;
use ArrudaFreitas\GovbrPades\Exceptions\TransportFailureException;

final class CurlHttpClient implements HttpClient
{
    private const DEFAULT_TIMEOUT = 30;
    private const DEFAULT_CONNECT_TIMEOUT = 10;

    private int $timeout;
    private int $connectTimeout;

    public function __construct(
        int $timeout = self::DEFAULT_TIMEOUT,
        int $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT
    ) {
        if ($timeout < 1) {
            throw InvalidConfigurationException::nonPositiveTimeout('timeout', $timeout);
        }

        if ($connectTimeout < 1) {
            throw InvalidConfigurationException::nonPositiveTimeout('connect_timeout', $connectTimeout);
        }

        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
    }

    public function send(Request $request): Response
    {
        $handle = curl_init();

        if ($handle === false) {
            throw TransportFailureException::couldNotStart();
        }

        curl_setopt_array($handle, [
            CURLOPT_URL => $request->url(),
            CURLOPT_CUSTOMREQUEST => $request->method(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_HTTPHEADER => $this->formatHeaders($request->headers()),
        ]);

        $body = $request->body();

        if ($body !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }

        $rawResponse = curl_exec($handle);
        $errorNumber = curl_errno($handle);
        $errorMessage = curl_error($handle);
        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);

        if (PHP_VERSION_ID < 80000) {
            curl_close($handle);
        }

        if ($errorNumber !== 0 || !is_string($rawResponse)) {
            throw TransportFailureException::requestFailed($errorNumber, $errorMessage);
        }

        return new Response(
            $statusCode,
            $this->parseHeaders(substr($rawResponse, 0, $headerSize)),
            substr($rawResponse, $headerSize)
        );
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<int, string>
     */
    private function formatHeaders(array $headers): array
    {
        $formatted = [];

        foreach ($headers as $name => $value) {
            $formatted[] = $name . ': ' . $value;
        }

        return $formatted;
    }

    /**
     * @return array<string, string>
     */
    private function parseHeaders(string $rawHeaders): array
    {
        $headers = [];

        foreach (explode("\r\n", $rawHeaders) as $line) {
            $parts = explode(':', $line, 2);

            if (count($parts) === 2) {
                $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
        }

        return $headers;
    }
}
