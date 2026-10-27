<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Http;

use ArrudaFreitas\GovbrPades\Exceptions\TransportFailureException;

interface HttpClient
{
    /**
     * A response that arrived is returned whatever its status code; only the lack of one
     * (DNS, TLS, connection, timeout) throws.
     *
     * @throws TransportFailureException
     */
    public function send(Request $request): Response;
}
