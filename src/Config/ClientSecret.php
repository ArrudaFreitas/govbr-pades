<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Config;

use ArrudaFreitas\GovbrPades\Exceptions\InvalidConfigurationException;

final class ClientSecret
{
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        if (trim($value) === '') {
            throw InvalidConfigurationException::emptyValue(
                'client_secret'
            );
        }

        return new self($value);
    }

    public function reveal(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return '***';
    }

    /**
     * Masks var_dump() and print_r() only: var_export() has no hook and always prints the
     * real value, so this object must never be var_export()ed.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['value' => '***'];
    }
}
