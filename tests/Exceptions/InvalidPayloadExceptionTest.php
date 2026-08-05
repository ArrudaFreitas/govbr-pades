<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Exceptions;

use ArrudaFreitas\GovbrPades\Exceptions\InvalidPayloadException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ArrudaFreitas\GovbrPades\Exceptions\InvalidPayloadException
 */
final class InvalidPayloadExceptionTest extends TestCase
{
    public function testNotEncodableCarriesTheReasonReportedByJson(): void
    {
        $exception = InvalidPayloadException::notEncodable('Malformed UTF-8 characters');

        self::assertStringContainsString('JSON', $exception->getMessage());
        self::assertStringContainsString('Malformed UTF-8 characters', $exception->getMessage());
    }
}
