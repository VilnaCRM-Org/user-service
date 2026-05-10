<?php

declare(strict_types=1);

namespace App\User\Application\Passkey;

use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;

final readonly class PasskeyWebauthnFactory implements PasskeyWebauthnFactoryInterface
{
    public function __construct(private PasskeyConfiguration $configuration)
    {
    }

    #[\Override]
    public function createSerializer(): SerializerInterface
    {
        return (new WebauthnSerializerFactory($this->createAttestationManager()))->create();
    }

    #[\Override]
    public function createAttestationValidator(): AuthenticatorAttestationResponseValidator
    {
        $factory = $this->createCeremonyFactory();

        return new AuthenticatorAttestationResponseValidator($factory->creationCeremony());
    }

    #[\Override]
    public function createAssertionValidator(): AuthenticatorAssertionResponseValidator
    {
        $factory = $this->createCeremonyFactory();

        return new AuthenticatorAssertionResponseValidator($factory->requestCeremony());
    }

    private function createCeremonyFactory(): CeremonyStepManagerFactory
    {
        $factory = new CeremonyStepManagerFactory();
        $factory->setAllowedOrigins($this->configuration->getAllowedOrigins());

        return $factory;
    }

    private function createAttestationManager(): AttestationStatementSupportManager
    {
        return new AttestationStatementSupportManager();
    }
}
