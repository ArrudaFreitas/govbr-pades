<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Config;

use ArrudaFreitas\GovbrPades\Exceptions\InvalidConfigurationException;

/**
 * Wraps the OAuth client secret so that it never reaches a stack trace. PHP includes
 * call arguments in traces whenever zend.exception_ignore_args=0, which is the built-in
 * default; passing the secret as a plain string would expose it in every exception
 * thrown below the call site.
 *
 * __debugInfo() additionally masks the value for var_dump() and print_r(). It does NOT
 * mask var_export(): that function must emit re-evaluable PHP code, so it always prints
 * the real property value and offers no hook to override it. This is still true as of
 * PHP 8.4, and it is a property of var_export() rather than a defect to be fixed here.
 * Never var_export() this object.
 *
 * On PHP 8.2+ the #[\SensitiveParameter] attribute covers the stack trace vector alone;
 * this wrapper is kept because it also protects the value when the surrounding
 * configuration object is dumped or logged.
 */
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
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['value' => '***'];
    }
}
