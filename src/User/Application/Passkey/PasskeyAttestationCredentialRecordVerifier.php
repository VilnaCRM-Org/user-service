<?php

declare(strict_types=1);

namespace App\User\Application\Passkey;

use App\User\Domain\Entity\PasskeyChallenge;
use Webauthn\CredentialRecord;

/**
 * @psalm-api
 */
final readonly class PasskeyAttestationCredentialRecordVerifier
{
    public function __construct(
        private PasskeyJsonCodecInterface $jsonCodec,
        private PasskeyWebauthnFactoryInterface $webauthnFactory,
        private PasskeyConfiguration $configuration,
        private PasskeyCredentialResponseResolver $responseResolver
    ) {
    }

    /**
     * @param array<string, scalar|array|null> $credential
     */
    public function verify(PasskeyChallenge $challenge, array $credential): CredentialRecord
    {
        $publicKeyCredential = $this->jsonCodec->decodeCredential($credential);

        return $this->webauthnFactory
            ->createAttestationValidator()
            ->check(
                $this->responseResolver->resolveAttestation($publicKeyCredential),
                $this->jsonCodec->decodeCreationOptions($challenge->getOptions()),
                $this->configuration->getRpId()
            );
    }
}
