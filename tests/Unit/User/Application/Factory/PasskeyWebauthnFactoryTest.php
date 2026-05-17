<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\PasskeyConfiguration;
use App\User\Application\Factory\PasskeyWebauthnFactory;
use LogicException;
use ReflectionMethod;
use stdClass;
use Symfony\Component\Serializer\SerializerInterface;

final class PasskeyWebauthnFactoryTest extends UnitTestCase
{
    public function testCreatesWebauthnCollaborators(): void
    {
        $factory = $this->createFactory();

        self::assertInstanceOf(SerializerInterface::class, $factory->createSerializer());
        self::assertIsObject($factory->createAttestationValidator());
        self::assertIsObject($factory->createAssertionValidator());
    }

    public function testRejectsUnavailableWebauthnClass(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Passkey WebAuthn class Webauthn\\Missing\\ClassName is not available.'
        );

        $this->invokeFactoryMethod('createWebauthnObject', 'Missing\\ClassName');
    }

    public function testRejectsUnavailableWebauthnMethod(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Passkey WebAuthn method missingMethod is not callable.');

        $this->invokeFactoryMethod('call', new stdClass(), 'missingMethod');
    }

    public function testRejectsInvalidSerializerResult(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Passkey serializer factory returned an invalid serializer.'
        );

        $this->invokeFactoryMethod('resolveSerializer', new stdClass());
    }

    private function createFactory(): PasskeyWebauthnFactory
    {
        return new PasskeyWebauthnFactory(new PasskeyConfiguration(
            'localhost',
            'VilnaCRM User Service',
            'https://localhost',
            300,
            300
        ));
    }

    private function invokeFactoryMethod(string $methodName, mixed ...$arguments): void
    {
        $method = new ReflectionMethod(PasskeyWebauthnFactory::class, $methodName);

        $method->invoke($this->createFactory(), ...$arguments);
    }
}
