<?php

declare(strict_types=1);

namespace App\User\Application\Passkey;

use App\User\Domain\Entity\PasskeyCredential;
use function array_map;
use Cose\Algorithms;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;

use Webauthn\PublicKeyCredentialUserEntity;

final readonly class PasskeyPublicKeyOptionsFactory
{
    public function __construct(
        private PasskeyConfiguration $configuration,
        private PasskeyEncoding $encoding
    ) {
    }

    /**
     * @param list<PasskeyCredential> $existingCredentials
     */
    public function createRegistrationOptions(
        string $email,
        string $userId,
        string $displayName,
        string $challengeBytes,
        array $existingCredentials
    ): PublicKeyCredentialCreationOptions {
        return new PublicKeyCredentialCreationOptions(
            $this->createRpEntity(),
            new PublicKeyCredentialUserEntity($email, $userId, $displayName),
            $challengeBytes,
            $this->createPublicKeyParameters(),
            $this->createAuthenticatorSelection(),
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            $this->createDescriptors($existingCredentials),
            $this->configuration->getTimeoutMilliseconds()
        );
    }

    /**
     * @param list<PasskeyCredential> $existingCredentials
     */
    public function createAuthenticationOptions(
        string $challengeBytes,
        array $existingCredentials
    ): PublicKeyCredentialRequestOptions {
        return new PublicKeyCredentialRequestOptions(
            $challengeBytes,
            $this->configuration->getRpId(),
            $this->createDescriptors($existingCredentials),
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            $this->configuration->getTimeoutMilliseconds()
        );
    }

    /**
     * @return list<PublicKeyCredentialParameters>
     */
    private function createPublicKeyParameters(): array
    {
        return [
            new PublicKeyCredentialParameters(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                Algorithms::COSE_ALGORITHM_ES256
            ),
            new PublicKeyCredentialParameters(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                Algorithms::COSE_ALGORITHM_RS256
            ),
        ];
    }

    private function createRpEntity(): PublicKeyCredentialRpEntity
    {
        return new PublicKeyCredentialRpEntity(
            $this->configuration->getRpName(),
            $this->configuration->getRpId()
        );
    }

    private function createAuthenticatorSelection(): AuthenticatorSelectionCriteria
    {
        return new AuthenticatorSelectionCriteria(
            null,
            AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_PREFERRED
        );
    }

    /**
     * @param list<PasskeyCredential> $credentials
     *
     * @return list<PublicKeyCredentialDescriptor>
     */
    private function createDescriptors(array $credentials): array
    {
        return array_map(
            fn (PasskeyCredential $credential): PublicKeyCredentialDescriptor => $this
                ->createDescriptor($credential),
            $credentials
        );
    }

    private function createDescriptor(PasskeyCredential $credential): PublicKeyCredentialDescriptor
    {
        return new PublicKeyCredentialDescriptor(
            PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            $this->encoding->decode($credential->getCredentialId())
        );
    }
}
