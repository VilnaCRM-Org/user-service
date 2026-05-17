<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\DTO\PasskeyConfiguration;

use function class_exists;
use function is_callable;
use function is_string;

use LogicException;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class PasskeyWebauthnFactory implements PasskeyWebauthnFactoryInterface
{
    public function __construct(private PasskeyConfiguration $configuration)
    {
    }

    #[\Override]
    public function createSerializer(): SerializerInterface
    {
        $serializerFactory = $this->createWebauthnObject(
            'Denormalizer\\WebauthnSerializerFactory',
            $this->createAttestationManager()
        );

        return $this->resolveSerializer($this->call($serializerFactory, 'create'));
    }

    #[\Override]
    public function createAttestationValidator(): object
    {
        $factory = $this->createCeremonyFactory();
        $ceremony = $this->call($factory, 'creationCeremony');

        return $this->createWebauthnObject(
            'AuthenticatorAttestationResponseValidator',
            $ceremony
        );
    }

    #[\Override]
    public function createAssertionValidator(): object
    {
        $factory = $this->createCeremonyFactory();
        $ceremony = $this->call($factory, 'requestCeremony');

        return $this->createWebauthnObject(
            'AuthenticatorAssertionResponseValidator',
            $ceremony
        );
    }

    private function createCeremonyFactory(): object
    {
        $factory = $this->createWebauthnObject('CeremonyStep\\CeremonyStepManagerFactory');
        $this->call($factory, 'setAllowedOrigins', $this->configuration->getAllowedOrigins());

        return $factory;
    }

    private function createAttestationManager(): object
    {
        return $this->createWebauthnObject(
            'AttestationStatement\\AttestationStatementSupportManager'
        );
    }

    private function createWebauthnObject(string $classSuffix, mixed ...$arguments): object
    {
        $className = $this->resolveWebauthnClass($classSuffix);

        if (!class_exists($className)) {
            throw new LogicException("Passkey WebAuthn class {$className} is not available.");
        }

        return new $className(...$arguments);
    }

    private function resolveSerializer(mixed $serializer): SerializerInterface
    {
        if (!$serializer instanceof SerializerInterface) {
            throw new LogicException('Passkey serializer factory returned an invalid serializer.');
        }

        return $serializer;
    }

    private function resolveWebauthnClass(string $classSuffix): string
    {
        return 'Webauthn\\' . $classSuffix;
    }

    private function call(object $object, string $method, mixed ...$arguments): mixed
    {
        $callable = [$object, $method];

        if (!is_callable($callable) || !is_string($callable[1])) {
            throw new LogicException("Passkey WebAuthn method {$method} is not callable.");
        }

        return $callable(...$arguments);
    }
}
