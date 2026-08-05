<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Exceptions;

use RuntimeException;

final class TransportFailureException extends RuntimeException implements GovbrPadesException
{
    public static function couldNotStart(): self
    {
        return new self('Unable to initialise a cURL handle.');
    }

    public static function requestFailed(int $errorNumber, string $errorMessage): self
    {
        return new self(sprintf('HTTP request failed (cURL error %d): %s', $errorNumber, $errorMessage));
    }
}
