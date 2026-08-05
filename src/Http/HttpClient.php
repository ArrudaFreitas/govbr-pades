<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Http;

use ArrudaFreitas\GovbrPades\Exceptions\TransportFailureException;

interface HttpClient
{
    /**
     * @throws TransportFailureException when nothing came back — DNS, TLS, connection
     *                                   or timeout. A response that did arrive is
     *                                   returned as is, whatever its status code.
     */
    public function send(Request $request): Response;
}
