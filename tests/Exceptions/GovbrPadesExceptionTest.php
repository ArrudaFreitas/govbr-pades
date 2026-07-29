<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Exceptions;

use ArrudaFreitas\GovbrPades\Exceptions\GovbrPadesException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

/**
 * @coversNothing
 */
final class GovbrPadesExceptionTest extends TestCase
{
    public function testImplementorIsCatchableThroughTheMarkerInterface(): void
    {
        $caught = null;

        try {
            throw $this->exampleException('simulated failure');
        } catch (GovbrPadesException $e) {
            $caught = $e;
        }

        self::assertInstanceOf(GovbrPadesException::class, $caught);
        self::assertSame('simulated failure', $caught->getMessage());
    }

    public function testImplementorRemainsANativeException(): void
    {
        $exception = $this->exampleException('simulated failure');

        self::assertInstanceOf(Throwable::class, $exception);
        self::assertInstanceOf(RuntimeException::class, $exception);
    }

    private function exampleException(string $message): GovbrPadesException
    {
        return new class ($message) extends RuntimeException implements GovbrPadesException {
        };
    }
}
