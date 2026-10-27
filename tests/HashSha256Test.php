<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test;

use ArrudaFreitas\GovbrPades\Exceptions\GovbrPadesException;
use ArrudaFreitas\GovbrPades\Exceptions\InvalidPayloadException;
use ArrudaFreitas\GovbrPades\HashSha256;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \ArrudaFreitas\GovbrPades\HashSha256
 *
 * @uses \ArrudaFreitas\GovbrPades\Exceptions\InvalidPayloadException
 */
final class HashSha256Test extends TestCase
{
    private const ABC_HEX = 'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad';
    private const ABC_BASE64 = 'ungWv48Bz+pBQUDeXa4iI7ADYaOWF3qctBD/YfIAFa0=';
    private const EMPTY_HEX = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';
    private const EMPTY_BASE64 = '47DEQpj8HBSa+/TImW+5JCeuQeRkm5NMpJWZG3hSuFU=';

    public function testOfBytesMatchesTheKnownVectorForAbc(): void
    {
        $hash = HashSha256::ofBytes('abc');

        self::assertSame(self::ABC_HEX, $hash->hex());
        self::assertSame(self::ABC_BASE64, $hash->base64());
    }

    public function testOfBytesMatchesTheKnownVectorForTheEmptyInput(): void
    {
        $hash = HashSha256::ofBytes('');

        self::assertSame(self::EMPTY_HEX, $hash->hex());
        self::assertSame(self::EMPTY_BASE64, $hash->base64());
    }

    public function testTheRawDigestIsAlwaysThirtyTwoBytes(): void
    {
        self::assertSame(32, strlen(HashSha256::ofBytes('')->raw()));
        self::assertSame(32, strlen(HashSha256::ofBytes('abc')->raw()));
        self::assertSame(32, strlen(HashSha256::ofBytes(random_bytes(4096))->raw()));
    }

    public function testBinaryInputIsHashedByteForByte(): void
    {
        $bytes = "\x00\x01\xff\xfe\r\n%PDF-1.7\x00";

        self::assertSame(hash('sha256', $bytes, true), HashSha256::ofBytes($bytes)->raw());
    }

    public function testTheThreeRepresentationsDescribeTheSameDigest(): void
    {
        $hash = HashSha256::ofBytes('abc');

        self::assertSame($hash->raw(), base64_decode($hash->base64(), true));
        self::assertSame($hash->raw(), hex2bin($hash->hex()));
    }

    public function testFromRawDigestAcceptsThirtyTwoBytes(): void
    {
        $digest = hash('sha256', 'abc', true);

        self::assertSame(self::ABC_HEX, HashSha256::fromRawDigest($digest)->hex());
    }

    /**
     * @dataProvider valuesThatAreNotRawDigests
     */
    public function testFromRawDigestRejectsAnythingThatIsNotThirtyTwoBytes(string $value): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('32 raw bytes');

        HashSha256::fromRawDigest($value);
    }

    /**
     * @return array<string, array{string}>
     */
    public function valuesThatAreNotRawDigests(): array
    {
        return [
            'empty' => [''],
            'the same digest in hex' => [self::ABC_HEX],
            'the same digest in base64' => [self::ABC_BASE64],
            'one byte short' => [substr(hash('sha256', 'abc', true), 0, 31)],
            'one byte too long' => [hash('sha256', 'abc', true) . "\x00"],
        ];
    }

    public function testTheRejectionIsCatchableThroughTheMarkerInterface(): void
    {
        $this->expectException(GovbrPadesException::class);

        HashSha256::fromRawDigest('too short');
    }

    public function testCannotBeInstantiatedDirectly(): void
    {
        $constructor = (new ReflectionClass(HashSha256::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPrivate());
    }
}
