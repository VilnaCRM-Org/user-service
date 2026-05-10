<?php

declare(strict_types=1);

namespace App\User\Application\Passkey;

use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\DTO\PasskeyOptionsResult;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use DateTimeImmutable;

final readonly class PasskeyAuthenticationService implements PasskeyAuthenticationServiceInterface
{
    public function __construct(
        private PasskeyUserResolver $userResolver,
        private PasskeyCredentialStore $credentialStore,
        private PasskeyChallengeStore $challengeStore,
        private PasskeyOptionsFactory $optionsFactory,
        private PasskeyCredentialVerifierInterface $credentialVerifier,
        private PasskeySessionIssuer $sessionIssuer
    ) {
    }

    #[\Override]
    public function start(string $email, bool $rememberMe): PasskeyOptionsResult
    {
        $userId = $this->userResolver->findUserIdByEmail($email);
        $credentials = $userId !== null
            ? $this->credentialStore->findByUserId($userId)
            : [];

        return $this->optionsFactory->createAuthenticationOptions(
            $email,
            $rememberMe,
            $userId,
            $credentials
        );
    }

    /**
     * @param array<string, scalar|array|null> $credential
     */
    #[\Override]
    public function complete(
        string $challengeId,
        array $credential,
        string $ipAddress,
        string $userAgent
    ): PasskeyAuthenticationResult {
        $challenge = $this->challengeStore->resolveAuthentication($challengeId);
        $challengeUserId = $this->challengeStore->requireUserId($challenge);
        $storedCredential = $this->resolveStoredCredential($credential, $challengeUserId);
        $user = $this->userResolver->resolveCredentialOwner($challengeUserId);
        $now = $this->verifyAndMarkUsed($challenge, $credential, $storedCredential);

        $this->challengeStore->delete($challenge);

        return $this->sessionIssuer->issue(
            $user,
            $challenge->isRememberMe(),
            $ipAddress,
            $userAgent,
            $now
        );
    }

    /**
     * @param array<string, scalar|array|null> $credential
     */
    private function resolveStoredCredential(array $credential, string $userId): PasskeyCredential
    {
        $credentialId = $this->credentialVerifier->extractCredentialId($credential);

        return $this->credentialStore->resolveForUser($credentialId, $userId);
    }

    /**
     * @param array<string, scalar|array|null> $credential
     */
    private function verifyAndMarkUsed(
        PasskeyChallenge $challenge,
        array $credential,
        PasskeyCredential $storedCredential
    ): DateTimeImmutable {
        $verifiedCredential = $this->credentialVerifier->verifyAssertion(
            $challenge,
            $credential,
            $storedCredential
        );
        $now = new DateTimeImmutable();

        $this->credentialStore->markUsed(
            $storedCredential,
            $verifiedCredential->getCredentialRecord(),
            $now
        );

        return $now;
    }
}
