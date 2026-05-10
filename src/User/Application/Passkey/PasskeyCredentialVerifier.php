<?php

declare(strict_types=1);

namespace App\User\Application\Passkey;

use App\User\Application\DTO\VerifiedPasskeyCredential;
use App\User\Application\Factory\PasskeyVerifiedCredentialFactory;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

/**
 * @psalm-api
 */
final class PasskeyCredentialVerifier implements PasskeyCredentialVerifierInterface
{
    public function __construct(
        private readonly PasskeyJsonCodecInterface $jsonCodec,
        private readonly PasskeyEncoding $encoding,
        private readonly PasskeyAttestationCredentialRecordVerifier $attestationVerifier,
        private readonly PasskeyAssertionCredentialRecordVerifier $assertionVerifier,
        private readonly PasskeyVerifiedCredentialFactory $verifiedCredentialFactory
    ) {
    }

    /**
     * @param array<string, scalar|array|null> $credential
     */
    #[\Override]
    public function extractCredentialId(array $credential): string
    {
        try {
            return $this->encoding->encode(
                $this->jsonCodec->decodeCredential($credential)->rawId
            );
        } catch (Throwable $exception) {
            throw new BadRequestHttpException('Invalid passkey credential payload.', $exception);
        }
    }

    /**
     * @param array<string, scalar|array|null> $credential
     */
    #[\Override]
    public function verifyAttestation(
        PasskeyChallenge $challenge,
        array $credential
    ): VerifiedPasskeyCredential {
        return $this->verifyCredential(
            fn (): VerifiedPasskeyCredential => $this->verifiedCredentialFactory->create(
                $this->attestationVerifier->verify($challenge, $credential)
            )
        );
    }

    /**
     * @param array<string, scalar|array|null> $credential
     */
    #[\Override]
    public function verifyAssertion(
        PasskeyChallenge $challenge,
        array $credential,
        PasskeyCredential $storedCredential
    ): VerifiedPasskeyCredential {
        return $this->verifyCredential(
            fn (): VerifiedPasskeyCredential => $this->verifiedCredentialFactory->create(
                $this->assertionVerifier->verify($challenge, $storedCredential, $credential)
            )
        );
    }

    /**
     * @param callable(): VerifiedPasskeyCredential $verification
     */
    private function verifyCredential(callable $verification): VerifiedPasskeyCredential
    {
        try {
            return $verification();
        } catch (BadRequestHttpException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new UnauthorizedHttpException(
                'Bearer',
                'Invalid passkey credential.',
                $exception
            );
        }
    }
}
