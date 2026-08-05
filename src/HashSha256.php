<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades;

use ArrudaFreitas\GovbrPades\Exceptions\InvalidPayloadException;

/**
 * A SHA-256 digest, always 32 raw bytes.
 */
final class HashSha256
{
    private const RAW_LENGTH = 32;

    private string $digest;

    private function __construct(string $digest)
    {
        $this->digest = $digest;
    }

    public static function ofBytes(string $bytes): self
    {
        return new self(hash('sha256', $bytes, true));
    }

    /**
     * For a digest computed elsewhere. Expects the 32 raw bytes, never hex or base64 —
     * hence the length check, which is the only thing that separates the three.
     *
     * @throws InvalidPayloadException
     */
    public static function fromRawDigest(string $digest): self
    {
        if (strlen($digest) !== self::RAW_LENGTH) {
            throw InvalidPayloadException::invalidDigestLength(self::RAW_LENGTH, strlen($digest));
        }

        return new self($digest);
    }

    public function raw(): string
    {
        return $this->digest;
    }

    public function base64(): string
    {
        return base64_encode($this->digest);
    }

    public function hex(): string
    {
        return bin2hex($this->digest);
    }
}
