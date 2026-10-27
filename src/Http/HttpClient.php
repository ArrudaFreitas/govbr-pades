<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Http;

use ArrudaFreitas\GovbrPades\Exceptions\TransportFailureException;

interface HttpClient
{
    public function send(Request $request): Response;
}
