<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Http;

use ArrudaFreitas\GovbrPades\Config\Environment;
use ArrudaFreitas\GovbrPades\Http\CurlHttpClient;
use ArrudaFreitas\GovbrPades\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * The success path of the real cURL client, exercised against PHP's built-in server on
 * the loopback interface — no external network involved. This is exactly what
 * Environment::custom() exists for: pointing the library at a fake server.
 *
 * It is also the only place the byte-for-byte fidelity of Response::rawBody() is proven
 * over a real socket rather than promised in a comment.
 *
 * @covers \ArrudaFreitas\GovbrPades\Http\CurlHttpClient
 *
 * @uses \ArrudaFreitas\GovbrPades\Config\Environment
 * @uses \ArrudaFreitas\GovbrPades\Http\Request
 * @uses \ArrudaFreitas\GovbrPades\Http\Response
 */
final class CurlHttpClientLocalServerTest extends TestCase
{
    private const ROUTER = __DIR__ . '/../fixtures/http-server/router.php';

    /** @var resource|null */
    private static $server;

    private static Environment $environment;

    public static function setUpBeforeClass(): void
    {
        $port = self::findFreePort();

        $process = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . $port, self::ROUTER],
            [
                0 => ['file', '/dev/null', 'r'],
                1 => ['file', '/dev/null', 'w'],
                2 => ['file', '/dev/null', 'w'],
            ],
            $pipes
        );

        if (!is_resource($process)) {
            self::fail('Unable to start the local PHP server.');
        }

        self::$server = $process;
        self::$environment = Environment::custom(
            'http://127.0.0.1:' . $port . '/oauth',
            'http://127.0.0.1:' . $port . '/api'
        );

        self::waitUntilListening($port);
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
            self::$server = null;
        }
    }

    public function testAPostedFormArrivesIntactAndTheAnswerComesBackParsed(): void
    {
        $fields = [
            'grant_type' => 'authorization_code',
            'code' => 'a-code with spaces&and=separators',
            'redirect_uri' => 'https://example.test/volta',
        ];

        $response = $this->client()->send(
            Request::postForm(self::$environment->oauthEndpoint('token'), $fields)
        );

        self::assertSame(200, $response->statusCode());
        self::assertTrue($response->isSuccessful());
        self::assertSame('application/json', $response->header('content-type'));

        $echoed = json_decode($response->rawBody(), true);

        self::assertIsArray($echoed);
        self::assertSame('POST', $echoed['method']);
        self::assertSame('application/x-www-form-urlencoded', $echoed['contentType']);
        self::assertSame(http_build_query($fields), $echoed['rawBody']);
    }

    public function testABinaryBodyComesBackByteForByteAndTheBearerHeaderIsSent(): void
    {
        $allByteValues = implode('', array_map('chr', range(0, 255)));

        $response = $this->client()->send(
            Request::postJson(self::$environment->apiEndpoint('assinarPKCS7'), ['hashBase64' => 'x'])
                ->withHeader('Authorization', 'Bearer AT-token-de-teste')
        );

        self::assertSame(200, $response->statusCode());
        self::assertSame('Bearer AT-token-de-teste', $response->header('x-received-authorization'));
        self::assertSame(256, strlen($response->rawBody()));
        self::assertSame($allByteValues, $response->rawBody());
    }

    public function testAnErrorStatusComesBackAsAResponseNotAnException(): void
    {
        $response = $this->client()->send(
            Request::get(self::$environment->apiEndpoint('caminho-que-nao-existe'))
        );

        self::assertSame(404, $response->statusCode());
        self::assertFalse($response->isSuccessful());
    }

    private function client(): CurlHttpClient
    {
        return new CurlHttpClient(5, 2);
    }

    private static function findFreePort(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errorNumber, $errorMessage);

        if ($socket === false) {
            self::fail(sprintf('Unable to reserve a port: %s.', $errorMessage));
        }

        $name = stream_socket_get_name($socket, false);
        fclose($socket);

        if ($name === false) {
            self::fail('Unable to read the reserved port.');
        }

        return (int) substr($name, (int) strrpos($name, ':') + 1);
    }

    private static function waitUntilListening(int $port): void
    {
        $deadline = microtime(true) + 5.0;

        do {
            $connection = @stream_socket_client('tcp://127.0.0.1:' . $port, $errorNumber, $errorMessage, 0.2);

            if (is_resource($connection)) {
                fclose($connection);

                return;
            }

            usleep(50000);
        } while (microtime(true) < $deadline);

        self::fail('The local PHP server did not start listening within 5 seconds.');
    }
}
