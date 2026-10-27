<?php

declare(strict_types=1);

namespace ArrudaFreitas\GovbrPades\Test\Exceptions;

use ArrudaFreitas\GovbrPades\Exceptions\UnexpectedResponseException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * @covers \ArrudaFreitas\GovbrPades\Exceptions\UnexpectedResponseException
 */
final class UnexpectedResponseExceptionTest extends TestCase
{
    public function testUnexpectedStatusExposesTheCodeWithoutParsingTheMessage(): void
    {
        $exception = UnexpectedResponseException::unexpectedStatus(418);

        self::assertSame(418, $exception->statusCode());
        self::assertStringContainsString('418', $exception->getMessage());
    }

    public function testEmptyBodyExposesTheCode(): void
    {
        $exception = UnexpectedResponseException::emptyBody(200);

        self::assertSame(200, $exception->statusCode());
        self::assertStringContainsString('empty body', $exception->getMessage());
    }

    public function testMalformedBodyCarriesTheReasonButNotTheBody(): void
    {
        $exception = UnexpectedResponseException::malformedBody(200, 'Syntax error');

        self::assertSame(200, $exception->statusCode());
        self::assertStringContainsString('Syntax error', $exception->getMessage());
    }

    public function testCannotBeInstantiatedDirectly(): void
    {
        $constructor = (new ReflectionClass(UnexpectedResponseException::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPrivate());
    }

    public function testNoFactoryAcceptsAnObject(): void
    {
        $reflection = new ReflectionClass(UnexpectedResponseException::class);

        foreach ($reflection->getMethods(ReflectionMethod::IS_STATIC) as $method) {
            foreach ($method->getParameters() as $parameter) {
                $type = $parameter->getType();

                self::assertInstanceOf(ReflectionNamedType::class, $type);

                if (!$type instanceof ReflectionNamedType) {
                    continue;
                }

                self::assertTrue(
                    $type->isBuiltin(),
                    sprintf(
                        'UnexpectedResponseException::%s() must not accept objects.',
                        $method->getName()
                    )
                );
            }
        }
    }
}
