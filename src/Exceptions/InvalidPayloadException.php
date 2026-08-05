<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Exceptions;

use InvalidArgumentException;

final class InvalidPayloadException extends InvalidArgumentException implements GovbrPadesException
{
    public static function notEncodable(string $reason): self
    {
        return new self(sprintf('The request payload could not be encoded as JSON: %s.', $reason));
    }

    public static function invalidDigestLength(int $expected, int $actual): self
    {
        return new self(sprintf(
            'A SHA-256 digest must be %d raw bytes, %d given. Raw bytes are expected, not hex or base64.',
            $expected,
            $actual
        ));
    }
}
