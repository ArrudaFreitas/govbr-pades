<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Http;

use ArrudaFreitas\GovbrPades\Exceptions\GovbrPadesException;
use ArrudaFreitas\GovbrPades\Exceptions\InvalidConfigurationException;
use ArrudaFreitas\GovbrPades\Exceptions\TransportFailureException;
use ArrudaFreitas\GovbrPades\Http\CurlHttpClient;
use ArrudaFreitas\GovbrPades\Http\HttpClient;
use ArrudaFreitas\GovbrPades\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ArrudaFreitas\GovbrPades\Http\CurlHttpClient
 *
 * @uses \ArrudaFreitas\GovbrPades\Exceptions\InvalidConfigurationException
 * @uses \ArrudaFreitas\GovbrPades\Exceptions\TransportFailureException
 * @uses \ArrudaFreitas\GovbrPades\Http\Request
 */
final class CurlHttpClientTest extends TestCase
{
    private const UNREACHABLE_URL = 'http://127.0.0.1:1/oauth2.0/token';

    public function testItIsAnHttpClient(): void
    {
        self::assertInstanceOf(HttpClient::class, new CurlHttpClient());
    }

    public function testAConnectionThatNeverLandsBecomesATransportFailure(): void
    {
        $this->expectException(TransportFailureException::class);

        $this->failingClient()->send(Request::get(self::UNREACHABLE_URL));
    }

    public function testTheFailureIsCatchableThroughTheMarkerInterface(): void
    {
        $this->expectException(GovbrPadesException::class);

        $this->failingClient()->send(Request::get(self::UNREACHABLE_URL));
    }

    public function testTheFailureMessageIdentifiesItAsATransportProblem(): void
    {
        try {
            $this->failingClient()->send(Request::get(self::UNREACHABLE_URL));
            self::fail('Expected TransportFailureException was not thrown.');
        } catch (TransportFailureException $caught) {
            self::assertStringContainsString('HTTP request failed', $caught->getMessage());
            self::assertStringContainsString('cURL error', $caught->getMessage());
        }
    }

    public function testAFileUrlIsRefusedRatherThanReadFromTheLocalDisk(): void
    {
        $this->expectException(TransportFailureException::class);
        $this->expectExceptionMessage(sprintf('(cURL error %d)', CURLE_UNSUPPORTED_PROTOCOL));

        $this->failingClient()->send(Request::get('file://' . __FILE__));
    }

    /**
     * @dataProvider nonPositiveSeconds
     */
    public function testATimeoutThatWouldNeverFireIsRefused(int $seconds): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('timeout');

        new CurlHttpClient($seconds, 1);
    }

    /**
     * @dataProvider nonPositiveSeconds
     */
    public function testAConnectTimeoutThatWouldNeverFireIsRefused(int $seconds): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('connect_timeout');

        new CurlHttpClient(2, $seconds);
    }

    /**
     * @return array<string, array{int}>
     */
    public function nonPositiveSeconds(): array
    {
        return [
            'zero, which cURL reads as no timeout' => [0],
            'negative' => [-5],
        ];
    }

    private function failingClient(): CurlHttpClient
    {
        return new CurlHttpClient(2, 1);
    }
}
