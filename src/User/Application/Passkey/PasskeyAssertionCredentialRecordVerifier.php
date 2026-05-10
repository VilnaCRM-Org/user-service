<?php

declare(strict_types=1);

namespace App\User\Application\Passkey;

use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\CredentialRecord;

/**
 * @psalm-api
 */
final readonly class PasskeyAssertionCredentialRecordVerifier
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
    public function verify(
        PasskeyChallenge $challenge,
        PasskeyCredential $storedCredential,
        array $credential
    ): CredentialRecord {
        $publicKeyCredential = $this->jsonCodec->decodeCredential($credential);

        return $this->verifyResponse(
            $challenge,
            $storedCredential,
            $this->responseResolver->resolveAssertion($publicKeyCredential)
        );
    }

    private function verifyResponse(
        PasskeyChallenge $challenge,
        PasskeyCredential $storedCredential,
        AuthenticatorAssertionResponse $response
    ): CredentialRecord {
        return $this->webauthnFactory
            ->createAssertionValidator()
            ->check(
                $this->jsonCodec->decodeCredentialRecord($storedCredential->getCredentialRecord()),
                $response,
                $this->jsonCodec->decodeRequestOptions($challenge->getOptions()),
                $this->configuration->getRpId(),
                $challenge->getUserId()
            );
    }
}
