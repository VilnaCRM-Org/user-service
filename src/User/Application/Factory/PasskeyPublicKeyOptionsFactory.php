<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\DTO\PasskeyConfiguration;
use App\User\Application\Transformer\PasskeyEncodingTransformer;
use App\User\Domain\Entity\PasskeyCredential;

final readonly class PasskeyPublicKeyOptionsFactory
{
    private const ATTESTATION_NONE = 'none';
    private const COSE_ALGORITHM_ES256 = -7;
    private const COSE_ALGORITHM_RS256 = -257;
    private const CREDENTIAL_TYPE_PUBLIC_KEY = 'public-key';
    private const RESIDENT_KEY_PREFERRED = 'preferred';
    private const USER_VERIFICATION_REQUIRED = 'required';

    public function __construct(
        private PasskeyConfiguration $configuration,
        private PasskeyEncodingTransformer $encoding
    ) {
    }

    /**
     * @param iterable<PasskeyCredential> $existingCredentials
     */
    public function createRegistrationOptions(
        string $email,
        string $userId,
        string $displayName,
        string $challengeBytes,
        iterable $existingCredentials
    ): object {
        $creationOptionsClass = 'Webauthn\\PublicKeyCredentialCreationOptions';
        $userEntityClass = 'Webauthn\\PublicKeyCredentialUserEntity';

        return new $creationOptionsClass(
            $this->createRpEntity(),
            new $userEntityClass($email, $userId, $displayName),
            $challengeBytes,
            $this->createPublicKeyParameters(),
            $this->createAuthenticatorSelection(),
            self::ATTESTATION_NONE,
            $this->createDescriptors($existingCredentials),
            $this->configuration->getTimeoutMilliseconds()
        );
    }

    /**
     * @param iterable<PasskeyCredential> $existingCredentials
     */
    public function createAuthenticationOptions(
        string $challengeBytes,
        iterable $existingCredentials
    ): object {
        $requestOptionsClass = 'Webauthn\\PublicKeyCredentialRequestOptions';

        return new $requestOptionsClass(
            $challengeBytes,
            $this->configuration->getRpId(),
            $this->createDescriptors($existingCredentials),
            self::USER_VERIFICATION_REQUIRED,
            $this->configuration->getTimeoutMilliseconds()
        );
    }

    /**
     * @return list<object>
     */
    private function createPublicKeyParameters(): array
    {
        $parametersClass = 'Webauthn\\PublicKeyCredentialParameters';

        return [
            new $parametersClass(
                self::CREDENTIAL_TYPE_PUBLIC_KEY,
                self::COSE_ALGORITHM_ES256
            ),
            new $parametersClass(
                self::CREDENTIAL_TYPE_PUBLIC_KEY,
                self::COSE_ALGORITHM_RS256
            ),
        ];
    }

    private function createRpEntity(): object
    {
        $rpEntityClass = 'Webauthn\\PublicKeyCredentialRpEntity';

        return new $rpEntityClass(
            $this->configuration->getRpName(),
            $this->configuration->getRpId()
        );
    }

    private function createAuthenticatorSelection(): object
    {
        $selectionClass = 'Webauthn\\AuthenticatorSelectionCriteria';

        return new $selectionClass(
            null,
            self::USER_VERIFICATION_REQUIRED,
            self::RESIDENT_KEY_PREFERRED
        );
    }

    /**
     * @param iterable<PasskeyCredential> $credentials
     *
     * @return list<object>
     */
    private function createDescriptors(iterable $credentials): array
    {
        $descriptors = [];
        foreach ($credentials as $credential) {
            $descriptors[] = $this->createDescriptor($credential);
        }

        return $descriptors;
    }

    private function createDescriptor(PasskeyCredential $credential): object
    {
        $descriptorClass = 'Webauthn\\PublicKeyCredentialDescriptor';

        return new $descriptorClass(
            self::CREDENTIAL_TYPE_PUBLIC_KEY,
            $this->encoding->decode($credential->getCredentialId())
        );
    }
}
