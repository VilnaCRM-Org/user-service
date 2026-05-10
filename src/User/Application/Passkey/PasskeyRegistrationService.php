<?php

declare(strict_types=1);

namespace App\User\Application\Passkey;

use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\DTO\PasskeyOptionsResult;
use App\User\Domain\Entity\PasskeyCredential;
use DateTimeImmutable;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class PasskeyRegistrationService implements PasskeyRegistrationServiceInterface
{
    public function __construct(
        private PasskeyUserResolver $userResolver,
        private PasskeyCredentialStore $credentialStore,
        private PasskeyChallengeStore $challengeStore,
        private PasskeyOptionsFactory $optionsFactory,
        private PasskeyCredentialVerifierInterface $credentialVerifier,
        private PasskeySessionIssuer $sessionIssuer,
        private PasskeyUserCreator $userCreator,
        private UuidFactory $uuidFactory
    ) {
    }

    #[\Override]
    public function startSignup(
        string $email,
        string $initials,
        string $displayName
    ): PasskeyOptionsResult {
        $this->userResolver->assertEmailIsAvailable($email);

        return $this->optionsFactory->createSignupOptions(
            $email,
            $initials,
            $displayName,
            (string) $this->uuidFactory->create()
        );
    }

    /**
     * @param array<string, scalar|array|null> $credential
     */
    #[\Override]
    public function completeSignup(
        string $challengeId,
        array $credential,
        string $label,
        bool $rememberMe,
        string $ipAddress,
        string $userAgent
    ): PasskeyAuthenticationResult {
        $challenge = $this->challengeStore->resolveSignup($challengeId);
        $this->challengeStore->assertSignupChallengeIsComplete($challenge);
        $this->userResolver->assertEmailIsAvailable((string) $challenge->getEmail());

        $verifiedCredential = $this->credentialVerifier->verifyAttestation($challenge, $credential);

        $now = new DateTimeImmutable();
        $user = $this->userCreator->createFromSignupChallenge($challenge);

        $this->credentialStore->register(
            $user->getId(),
            $verifiedCredential,
            $label,
            $now
        );
        $this->challengeStore->delete($challenge);

        return $this->sessionIssuer->issue($user, $rememberMe, $ipAddress, $userAgent, $now);
    }

    #[\Override]
    public function startRegistration(
        string $userId,
        string $email
    ): PasskeyOptionsResult {
        $user = $this->userResolver->resolveAuthenticated($userId);

        return $this->optionsFactory->createRegistrationOptions(
            $email,
            $user->getInitials(),
            $user->getId(),
            $this->credentialStore->findByUserId($user->getId())
        );
    }

    /**
     * @param array<string, scalar|array|null> $credential
     */
    #[\Override]
    public function completeRegistration(
        string $challengeId,
        array $credential,
        string $label,
        string $currentUserId
    ): PasskeyCredential {
        $challenge = $this->challengeStore->resolveRegistration($challengeId);
        $this->challengeStore->assertBelongsToUser($challenge, $currentUserId);

        $verifiedCredential = $this->credentialVerifier->verifyAttestation($challenge, $credential);
        $passkeyCredential = $this->credentialStore->register(
            $currentUserId,
            $verifiedCredential,
            $label,
            new DateTimeImmutable()
        );

        $this->challengeStore->delete($challenge);

        return $passkeyCredential;
    }
}
