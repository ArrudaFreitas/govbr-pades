<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Exceptions;

use RuntimeException;

final class UnexpectedResponseException extends RuntimeException implements GovbrPadesException
{
    private int $statusCode;

    private function __construct(string $message, int $statusCode)
    {
        parent::__construct($message);

        $this->statusCode = $statusCode;
    }

    public static function unexpectedStatus(int $statusCode): self
    {
        return new self(sprintf('The API answered with an unexpected status %d.', $statusCode), $statusCode);
    }

    public static function emptyBody(int $statusCode): self
    {
        return new self(sprintf('The API answered %d with an empty body.', $statusCode), $statusCode);
    }

    public static function malformedBody(int $statusCode, string $reason): self
    {
        return new self(
            sprintf('The API answered %d with a body that could not be read: %s.', $statusCode, $reason),
            $statusCode
        );
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
