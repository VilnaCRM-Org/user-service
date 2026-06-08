<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\DTO\PasskeyConfiguration;
use App\User\Application\DTO\PasskeyOptionsResult;
use App\User\Application\Transformer\PasskeyEncodingTransformer;
use App\User\Application\Transformer\PasskeyJsonTransformerInterface;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Repository\PasskeyChallengeRepositoryInterface;
use App\User\Domain\ValueObject\PasskeyChallengeContext;
use DateTimeImmutable;

use function random_bytes;
use function strtolower;
use function trim;

final readonly class PasskeyOptionsFactory
{
    public function __construct(
        private PasskeyConfiguration $configuration,
        private PasskeyJsonTransformerInterface $jsonTransformer,
        private PasskeyEncodingTransformer $encoding,
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
        $normalizedEmail = $this->normalizeEmail($email);
        $resolvedDisplayName = $this->resolveDisplayName($displayName, $initials);

        return $this->createRegistrationResult(
            PasskeyChallenge::PURPOSE_SIGNUP,
            $normalizedEmail,
            $resolvedDisplayName,
            $userId,
            [],
            new PasskeyChallengeContext(
                $normalizedEmail,
                $initials,
                $resolvedDisplayName,
                $userId
            )
        );
    }

    /**
     * @param iterable<PasskeyCredential> $existingCredentials
     */
    public function createRegistrationOptions(
        string $email,
        string $displayName,
        string $userId,
        iterable $existingCredentials
    ): PasskeyOptionsResult {
        $normalizedEmail = $this->normalizeEmail($email);

        return $this->createRegistrationResult(
            PasskeyChallenge::PURPOSE_REGISTRATION,
            $normalizedEmail,
            $displayName,
            $userId,
            $existingCredentials,
            new PasskeyChallengeContext(
                $normalizedEmail,
                displayName: $displayName,
                userId: $userId
            )
        );
    }

    /**
     * @param iterable<PasskeyCredential> $existingCredentials
     */
    public function createAuthenticationOptions(
        string $email,
        bool $rememberMe,
        ?string $userId,
        iterable $existingCredentials
    ): PasskeyOptionsResult {
        $context = new PasskeyChallengeContext($this->normalizeEmail($email), userId: $userId);
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
     * @param iterable<PasskeyCredential> $existingCredentials
     */
    private function createRegistrationResult(
        string $purpose,
        string $email,
        string $displayName,
        string $userId,
        iterable $existingCredentials,
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
        object $options,
        DateTimeImmutable $createdAt,
        PasskeyChallengeContext $context
    ): PasskeyOptionsResult {
        $challenge = new PasskeyChallenge(
            $this->idFactory->create(),
            $purpose,
            $this->encoding->encode($challengeBytes),
            $this->jsonTransformer->encodeOptions($options),
            $createdAt,
            $this->configuration->challengeExpiresAt($createdAt),
            $context
        );

        $this->challengeRepository->save($challenge);

        return new PasskeyOptionsResult(
            $challenge,
            $this->jsonTransformer->optionsToArray($options)
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

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
