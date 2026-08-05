<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Http;

use ArrudaFreitas\GovbrPades\Http\HttpClient;
use ArrudaFreitas\GovbrPades\Http\Request;
use ArrudaFreitas\GovbrPades\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * The reason layer 1 exists: layers 2 and 4 have to be buildable without network,
 * without credentials and without a PDF. This proves the seam by replaying a real
 * gov.br refusal through a fake client.
 *
 * @coversNothing
 */
final class HttpClientContractTest extends TestCase
{
    private const BRONZE_BODY = 'Cidadão não possui a identidade (Prata ou Ouro) necessária '
        . 'para uso da assinatura eletrônica digital.';

    public function testAFakeClientCanReplayARealApiRefusal(): void
    {
        $canned = new Response(403, ['Content-Type' => 'text/plain'], self::BRONZE_BODY);

        $response = $this->clientAnswering($canned)
            ->send(Request::get('https://assinatura-api.staging.iti.br/externo/v2/certificadoPublico'));

        self::assertSame(403, $response->statusCode());
        self::assertFalse($response->isSuccessful());
        self::assertSame(self::BRONZE_BODY, $response->rawBody());
        self::assertSame('text/plain', $response->header('content-type'));
    }

    /**
     * A 403 is a legitimate answer that arrived. Deciding it means "bronze account" is
     * the business layer's job, not the transport's.
     */
    public function testTheTransportNeverTurnsAStatusCodeIntoAnException(): void
    {
        $canned = new Response(403, [], self::BRONZE_BODY);

        $response = $this->clientAnswering($canned)->send(Request::get('https://example.test/certificadoPublico'));

        self::assertSame(403, $response->statusCode());
    }

    private function clientAnswering(Response $response): HttpClient
    {
        return new class ($response) implements HttpClient {
            private Response $response;

            public function __construct(Response $response)
            {
                $this->response = $response;
            }

            public function send(Request $request): Response
            {
                return $this->response;
            }
        };
    }
}
