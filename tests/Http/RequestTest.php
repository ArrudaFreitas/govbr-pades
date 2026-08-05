<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Http;

use ArrudaFreitas\GovbrPades\Exceptions\GovbrPadesException;
use ArrudaFreitas\GovbrPades\Exceptions\InvalidPayloadException;
use ArrudaFreitas\GovbrPades\Http\Request;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \ArrudaFreitas\GovbrPades\Http\Request
 *
 * @uses \ArrudaFreitas\GovbrPades\Exceptions\InvalidPayloadException
 */
final class RequestTest extends TestCase
{
    private const TOKEN_URL = 'https://cas.staging.iti.br/oauth2.0/token';
    private const CERTIFICATE_URL = 'https://assinatura-api.staging.iti.br/externo/v2/certificadoPublico';
    private const SIGN_URL = 'https://assinatura-api.staging.iti.br/externo/v2/assinarPKCS7';
    private const HASH = 'kmm8XNQNIzSHTKAC2W0G2fFbxGy24kniLuUAZjZbFb0=';

    public function testGetCarriesNeitherBodyNorHeaders(): void
    {
        $request = Request::get(self::CERTIFICATE_URL);

        self::assertSame('GET', $request->method());
        self::assertSame(self::CERTIFICATE_URL, $request->url());
        self::assertNull($request->body());
        self::assertSame([], $request->headers());
    }

    public function testPostFormEncodesTheBodyAndSetsItsOwnContentType(): void
    {
        $request = Request::postForm(self::TOKEN_URL, [
            'grant_type' => 'authorization_code',
            'code' => 'a code with spaces',
        ]);

        self::assertSame('POST', $request->method());
        self::assertSame('application/x-www-form-urlencoded', $request->headers()['content-type']);
        self::assertSame('grant_type=authorization_code&code=a+code+with+spaces', $request->body());
    }

    public function testPostJsonEncodesTheBodyAndSetsItsOwnContentType(): void
    {
        $request = Request::postJson(self::SIGN_URL, ['hashBase64' => self::HASH]);

        self::assertSame('POST', $request->method());
        self::assertSame('application/json', $request->headers()['content-type']);
        self::assertSame('{"hashBase64":"' . self::HASH . '"}', $request->body());
    }

    /**
     * "\xB1\x31" is not valid UTF-8, so json_encode() refuses it. The point is not that
     * this input is likely — it is that the failure leaves through the library's own
     * exception instead of a JsonException nobody can catch generically.
     */
    public function testAPayloadThatCannotBeEncodedRaisesTheLibraryException(): void
    {
        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage('JSON');

        Request::postJson(self::SIGN_URL, ['hashBase64' => "\xB1\x31"]);
    }

    public function testThatFailureIsCatchableThroughTheMarkerInterface(): void
    {
        $this->expectException(GovbrPadesException::class);

        Request::postJson(self::SIGN_URL, ['hashBase64' => "\xB1\x31"]);
    }

    public function testWithHeaderAddsTheBearerWithoutDisturbingTheContentType(): void
    {
        $request = Request::postJson(self::SIGN_URL, ['hashBase64' => self::HASH])
            ->withHeader('Authorization', 'Bearer AT-183-eRE7ot2y3FpEOTCIo1gwnZ81LMmT5I8c');

        self::assertSame('Bearer AT-183-eRE7ot2y3FpEOTCIo1gwnZ81LMmT5I8c', $request->headers()['authorization']);
        self::assertSame('application/json', $request->headers()['content-type']);
    }

    public function testHeaderNamesAreCaseInsensitiveSoNothingIsEverSentTwice(): void
    {
        $request = Request::postJson(self::SIGN_URL, ['hashBase64' => self::HASH])
            ->withHeader('Content-Type', 'application/problem+json');

        self::assertSame(['content-type' => 'application/problem+json'], $request->headers());
    }

    public function testWithHeaderLeavesTheOriginalUntouched(): void
    {
        $original = Request::get(self::CERTIFICATE_URL);

        $modified = $original->withHeader('Authorization', 'Bearer AT-183-token');

        self::assertNotSame($original, $modified);
        self::assertSame([], $original->headers());
        self::assertSame(['authorization' => 'Bearer AT-183-token'], $modified->headers());
    }

    public function testCannotBeInstantiatedDirectly(): void
    {
        $constructor = (new ReflectionClass(Request::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPrivate());
    }
}
