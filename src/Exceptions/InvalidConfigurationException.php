<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Exceptions;

use InvalidArgumentException;

final class InvalidConfigurationException extends InvalidArgumentException implements GovbrPadesException
{
    public static function emptyValue(string $field): self
    {
        return new self(sprintf('%s must not be empty.', $field));
    }

    public static function invalidUrl(string $field): self
    {
        return new self(sprintf('%s must be a valid URL.', $field));
    }

    public static function nonPositiveTimeout(string $field, int $seconds): self
    {
        return new self(sprintf(
            '%s must be a positive number of seconds, %d given. cURL reads zero as "no timeout at all".',
            $field,
            $seconds
        ));
    }
}
