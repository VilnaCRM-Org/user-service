<?php

declare(strict_types=1);

namespace App\User\Application\Passkey;

use App\User\Application\DTO\PasskeyOptionsResult;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Repository\PasskeyChallengeRepositoryInterface;
use App\User\Domain\ValueObject\PasskeyChallengeContext;
use DateTimeImmutable;

use function random_bytes;
use function trim;

use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

final readonly class PasskeyOptionsFactory
{
    public function __construct(
        private PasskeyConfiguration $configuration,
        private PasskeyJsonCodecInterface $jsonCodec,
        private PasskeyEncoding $encoding,
        private PasskeyPublicKeyOptionsFactory $publicKeyOptionsFactory,
        private PasskeyChallengeRepositoryInterface $challengeRepository,
        private IdFactoryInterface $idFactory
    ) {
    }

    public function createSignupOptions(
        string $email,
        string $initials,
        string $displayName,
        string $userId
    ): PasskeyOptionsResult {
        $resolvedDisplayName = $this->resolveDisplayName($displayName, $initials);

        return $this->createRegistrationResult(
            PasskeyChallenge::PURPOSE_SIGNUP,
            $email,
            $resolvedDisplayName,
            $userId,
            [],
            new PasskeyChallengeContext(
                $email,
                $initials,
                $resolvedDisplayName,
                $userId
            )
        );
    }

    /**
     * @param list<PasskeyCredential> $existingCredentials
     */
    public function createRegistrationOptions(
        string $email,
        string $displayName,
        string $userId,
        array $existingCredentials
    ): PasskeyOptionsResult {
        return $this->createRegistrationResult(
            PasskeyChallenge::PURPOSE_REGISTRATION,
            $email,
            $displayName,
            $userId,
            $existingCredentials,
            new PasskeyChallengeContext($email, displayName: $displayName, userId: $userId)
        );
    }

    /**
     * @param list<PasskeyCredential> $existingCredentials
     */
    public function createAuthenticationOptions(
        string $email,
        bool $rememberMe,
        ?string $userId,
        array $existingCredentials
    ): PasskeyOptionsResult {
        $context = new PasskeyChallengeContext($email, userId: $userId);
        $resolvedContext = $rememberMe ? $context->withRememberMe() : $context;
        $createdAt = new DateTimeImmutable();
        $challengeBytes = random_bytes(32);

        return $this->saveChallenge(
            PasskeyChallenge::PURPOSE_AUTHENTICATION,
            $challengeBytes,
            $this->publicKeyOptionsFactory->createAuthenticationOptions(
                $challengeBytes,
                $existingCredentials
            ),
            $createdAt,
            $resolvedContext
        );
    }

    /**
     * @param list<PasskeyCredential> $existingCredentials
     */
    private function createRegistrationResult(
        string $purpose,
        string $email,
        string $displayName,
        string $userId,
        array $existingCredentials,
        PasskeyChallengeContext $context
    ): PasskeyOptionsResult {
        $createdAt = new DateTimeImmutable();
        $challengeBytes = random_bytes(32);

        return $this->saveChallenge(
            $purpose,
            $challengeBytes,
            $this->publicKeyOptionsFactory->createRegistrationOptions(
                $email,
                $userId,
                $displayName,
                $challengeBytes,
                $existingCredentials
            ),
            $createdAt,
            $context
        );
    }

    private function saveChallenge(
        string $purpose,
        string $challengeBytes,
        PublicKeyCredentialCreationOptions|PublicKeyCredentialRequestOptions $options,
        DateTimeImmutable $createdAt,
        PasskeyChallengeContext $context
    ): PasskeyOptionsResult {
        $challenge = new PasskeyChallenge(
            $this->idFactory->create(),
            $purpose,
            $this->encoding->encode($challengeBytes),
            $this->jsonCodec->encodeOptions($options),
            $createdAt,
            $this->configuration->challengeExpiresAt($createdAt),
            $context
        );

        $this->challengeRepository->save($challenge);

        return new PasskeyOptionsResult(
            $challenge,
            $this->jsonCodec->optionsToArray($options)
        );
    }

    private function resolveDisplayName(string $displayName, string $fallback): string
    {
        $trimmedDisplayName = trim($displayName);
        if ($trimmedDisplayName !== '') {
            return $trimmedDisplayName;
        }

        return $fallback;
    }
}
