<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\CompleteTwoFactorCommand;
use App\User\Application\DTO\CompleteTwoFactorCommandResponse;
use App\User\Application\Service\IssuedSession;
use App\User\Application\Service\SessionIssuanceServiceInterface;
use App\User\Application\Service\TwoFactorCodeVerifierInterface;
use App\User\Application\Service\TwoFactorEventPublisherInterface;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 */
final readonly class CompleteTwoFactorCommandHandler implements CommandHandlerInterface
{
    private const RECOVERY_CODE_WARNING_THRESHOLD = 2;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository,
        private SessionIssuanceServiceInterface $sessionIssuanceService,
        private TwoFactorCodeVerifierInterface $codeVerifier,
        private TwoFactorEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(CompleteTwoFactorCommand $command): void
    {
        $pendingSession = $this->resolvePendingSession(
            $command->pendingSessionId
        );
        $user = $this->resolveUser($pendingSession->getUserId());
        $method = $this->codeVerifier->resolveVerificationMethod(
            $user,
            $command->twoFactorCode
        );

        if ($method === null) {
            $this->handleTwoFactorFailure($command);
        }

        $this->issueTokensAndComplete(
            $user,
            $command,
            $pendingSession,
            $method
        );
    }

    private function issueTokensAndComplete(
        User $user,
        CompleteTwoFactorCommand $command,
        PendingTwoFactor $pendingSession,
        ?string $method
    ): void {
        $issuedAt = new DateTimeImmutable();
        $issued = $this->sessionIssuanceService->issue(
            $user,
            $command->ipAddress,
            $command->userAgent,
            $pendingSession->isRememberMe(),
            $issuedAt
        );
        $remaining = $this->resolveRemainingCodes($user, $method);

        $this->pendingTwoFactorRepository->delete($pendingSession);
        $command->setResponse(
            $this->buildResponse($issued, $pendingSession, $remaining)
        );

        $this->publishEvents($user, $issued, $command, $method, $remaining);
    }

    /**
     * @psalm-return int<0, max>|null
     */
    private function resolveRemainingCodes(
        User $user,
        ?string $method
    ): ?int {
        if ($method !== 'recovery_code') {
            return null;
        }

        return $this->codeVerifier->countRemainingCodes($user->getId());
    }

    private function publishEvents(
        User $user,
        IssuedSession $issued,
        CompleteTwoFactorCommand $command,
        ?string $method,
        ?int $remaining
    ): void {
        if ($remaining !== null) {
            $this->eventPublisher->publishRecoveryCodeUsed(
                $user->getId(),
                $remaining
            );
        }

        $this->eventPublisher->publishCompleted(
            $user->getId(),
            $issued->sessionId,
            $command->ipAddress,
            $command->userAgent,
            $method
        );
    }

    private function buildResponse(
        IssuedSession $issued,
        PendingTwoFactor $pendingSession,
        ?int $remainingCodes
    ): CompleteTwoFactorCommandResponse {
        $warningMessage = $this->buildWarningMessage($remainingCodes);
        $codesForResponse = $warningMessage !== null ? $remainingCodes : null;
        $response = new CompleteTwoFactorCommandResponse(
            $issued->accessToken,
            $issued->refreshToken,
            $codesForResponse,
            $warningMessage
        );

        if ($pendingSession->isRememberMe()) {
            return $response->withRememberMe();
        }

        return $response;
    }

    private function buildWarningMessage(?int $remainingCodes): ?string
    {
        if ($remainingCodes === null || $remainingCodes > self::RECOVERY_CODE_WARNING_THRESHOLD) {
            return null;
        }

        if ($remainingCodes === 0) {
            return 'All recovery codes have been used. Regenerate immediately.';
        }

        return sprintf('Only %d recovery code(s) remaining. Regenerate soon.', $remainingCodes);
    }

    private function resolvePendingSession(string $pendingSessionId): PendingTwoFactor
    {
        $pendingSession = $this->pendingTwoFactorRepository->findById($pendingSessionId);
        if (!$pendingSession instanceof PendingTwoFactor || $pendingSession->isExpired()) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid or expired two-factor session.');
        }

        return $pendingSession;
    }

    private function resolveUser(string $userId): User
    {
        $user = $this->userRepository->findById($userId);
        if (!$user instanceof User || !$user->isTwoFactorEnabled()) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid or expired two-factor session.');
        }

        return $user;
    }

    private function handleTwoFactorFailure(CompleteTwoFactorCommand $command): never
    {
        $this->eventPublisher->publishFailed(
            $command->pendingSessionId,
            $command->ipAddress,
            'invalid_code'
        );

        throw new UnauthorizedHttpException('Bearer', 'Invalid two-factor code.');
    }
}
