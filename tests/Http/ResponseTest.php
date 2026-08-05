<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Http;

use ArrudaFreitas\GovbrPades\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ArrudaFreitas\GovbrPades\Http\Response
 */
final class ResponseTest extends TestCase
{
    public function testExposesTheStatusCode(): void
    {
        self::assertSame(403, (new Response(403, [], ''))->statusCode());
    }

    /**
     * The signature dies if a single byte changes, so this guards the one thing the
     * accessor promises: no trimming, no encoding conversion, no interpretation.
     */
    public function testRawBodyIsReturnedByteForByte(): void
    {
        $der = "\x30\x82\x01\x0a\x00\r\n \xff\xfe   ";

        $response = new Response(200, [], $der);

        self::assertSame($der, $response->rawBody());
        self::assertSame(strlen($der), strlen($response->rawBody()));
    }

    public function testAnEmptyBodyStaysAnEmptyString(): void
    {
        self::assertSame('', (new Response(204, [], ''))->rawBody());
    }

    public function testHeaderLookupIsCaseInsensitive(): void
    {
        $response = new Response(200, ['Content-Type' => 'application/json'], '');

        self::assertSame('application/json', $response->header('content-type'));
        self::assertSame('application/json', $response->header('Content-Type'));
        self::assertSame('application/json', $response->header('CONTENT-TYPE'));
    }

    public function testHeaderReturnsNullWhenAbsent(): void
    {
        self::assertNull((new Response(200, [], ''))->header('www-authenticate'));
    }

    /**
     * @dataProvider statusCodes
     */
    public function testIsSuccessfulCoversOnlyTheTwoHundreds(int $statusCode, bool $expected): void
    {
        self::assertSame($expected, (new Response($statusCode, [], ''))->isSuccessful());
    }

    /**
     * @return array<string, array{int, bool}>
     */
    public function statusCodes(): array
    {
        return [
            '200 ok' => [200, true],
            '204 no content' => [204, true],
            '299 upper bound' => [299, true],
            '302 gateway login redirect' => [302, false],
            '400 invalid_request' => [400, false],
            '401 unauthorized' => [401, false],
            '403 bronze account' => [403, false],
            '500 server error' => [500, false],
        ];
    }
}
