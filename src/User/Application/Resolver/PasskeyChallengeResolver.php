<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Repository\PasskeyChallengeRepositoryInterface;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

final readonly class PasskeyChallengeResolver
{
    private LoggerInterface $logger;

    public function __construct(
        private PasskeyChallengeRepositoryInterface $challengeRepository,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function resolveSignup(string $challengeId): PasskeyChallenge
    {
        return $this->resolveActive($challengeId, PasskeyChallenge::PURPOSE_SIGNUP);
    }

    public function resolveRegistrationForUser(
        string $challengeId,
        string $userId
    ): PasskeyChallenge {
        $now = new DateTimeImmutable();
        $challenge = $this->challengeRepository->claimActiveForUser(
            $challengeId,
            PasskeyChallenge::PURPOSE_REGISTRATION,
            $userId,
            $now
        );

        if (!$challenge instanceof PasskeyChallenge) {
            $this->denyChallenge();
        }

        return $challenge;
    }

    public function resolveAuthentication(string $challengeId): PasskeyChallenge
    {
        return $this->resolveActive($challengeId, PasskeyChallenge::PURPOSE_AUTHENTICATION);
    }

    public function assertSignupChallengeIsComplete(PasskeyChallenge $challenge): void
    {
        if (
            $challenge->getEmail() === null
            || $challenge->getInitials() === null
            || $challenge->getUserId() === null
        ) {
            $this->denyChallenge();
        }
    }

    public function assertBelongsToUser(PasskeyChallenge $challenge, string $userId): void
    {
        if ($challenge->getUserId() !== $userId) {
            $this->denyChallenge();
        }
    }

    public function delete(PasskeyChallenge $challenge): void
    {
        $this->challengeRepository->delete($challenge);
    }

    public function deleteBestEffort(PasskeyChallenge $challenge, string $failureMessage): void
    {
        try {
            $this->delete($challenge);
        } catch (Throwable $exception) {
            $this->logger->warning($failureMessage, [
                'challenge_id' => $challenge->getId(),
                'exception' => $exception,
            ]);
        }
    }

    private function resolveActive(string $challengeId, string $purpose): PasskeyChallenge
    {
        $now = new DateTimeImmutable();
        $challenge = $this->challengeRepository->claimActive($challengeId, $purpose, $now);

        if (!$challenge instanceof PasskeyChallenge) {
            $this->denyChallenge();
        }

        return $challenge;
    }

    private function denyChallenge(): never
    {
        throw new UnauthorizedHttpException('Bearer', 'Invalid or expired passkey challenge.');
    }
}
