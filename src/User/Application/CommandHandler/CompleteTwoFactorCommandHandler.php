<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\CompleteTwoFactorCommand;
use App\User\Application\Component\SessionIssuerInterface;
use App\User\Application\Component\TwoFactorCodeVerifierInterface;
use App\User\Application\Component\TwoFactorEventsInterface;
use App\User\Application\DTO\CompleteTwoFactorCommandResponse;
use App\User\Application\DTO\IssuedSession;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @psalm-api
 */
final readonly class CompleteTwoFactorCommandHandler implements CommandHandlerInterface
{
    private const RECOVERY_CODE_WARNING_THRESHOLD = 2;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository,
        private SessionIssuerInterface $sessionIssuer,
        private TwoFactorCodeVerifierInterface $twoFactorCodeVerifier,
        private TwoFactorEventsInterface $events,
    ) {
    }

    public function __invoke(CompleteTwoFactorCommand $command): void
    {
        $pendingSession = $this->resolvePendingSession($command->pendingSessionId);
        $user = $this->resolveUser($pendingSession->getUserId());
        $method = $this->twoFactorCodeVerifier->verifyAndResolveMethod(
            $user,
            $command->twoFactorCode
        );

        if ($method === null) {
            $this->handleTwoFactorFailure($command);
        }

        assert(is_string($method));
        $rememberMe = $pendingSession->isRememberMe();
        $this->consumePendingSessionOrFail($pendingSession->getId());
        $this->issueTokensAndComplete($user, $command, $rememberMe, $method);
    }

    private function issueTokensAndComplete(
        User $user,
        CompleteTwoFactorCommand $command,
        bool $rememberMe,
        string $method
    ): void {
        $issued = $this->issueSession(
            $user,
            $command->ipAddress,
            $command->userAgent,
            $rememberMe
        );
        $remaining = $this->resolveRemainingCodes($user, $method);

        $command->setResponse($this->buildResponse($issued, $rememberMe, $remaining));

        $this->publishEvents($user, $issued, $command, $method, $remaining);
    }

    private function issueSession(
        User $user,
        string $ipAddress,
        string $userAgent,
        bool $rememberMe
    ): IssuedSession {
        return $this->sessionIssuer->issue(
            $user,
            $ipAddress,
            $userAgent,
            $rememberMe,
            new DateTimeImmutable()
        );
    }

    /**
     * @psalm-return int<0, max>|null
     */
    private function resolveRemainingCodes(User $user, string $method): ?int
    {
        if ($method !== TwoFactorCodeVerifierInterface::METHOD_RECOVERY_CODE) {
            return null;
        }

        return $this->twoFactorCodeVerifier->countRemainingCodes($user->getId());
    }

    private function publishEvents(
        User $user,
        IssuedSession $issued,
        CompleteTwoFactorCommand $command,
        string $method,
        ?int $remaining
    ): void {
        if ($remaining !== null) {
            $this->events->publishRecoveryCodeUsed($user->getId(), $remaining);
        }

        $this->events->publishCompleted(
            $user->getId(),
            $user->getEmail(),
            $issued->sessionId,
            $command->ipAddress,
            $command->userAgent,
            $method
        );
    }

    private function buildResponse(
        IssuedSession $issued,
        bool $rememberMe,
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

        if ($rememberMe) {
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

    private function consumePendingSessionOrFail(string $pendingSessionId): void
    {
        if (
            $this->pendingTwoFactorRepository->consumeIfActive(
                $pendingSessionId,
                new DateTimeImmutable()
            )
        ) {
            return;
        }

        throw new UnauthorizedHttpException('Bearer', 'Invalid or expired two-factor session.');
    }

    private function handleTwoFactorFailure(CompleteTwoFactorCommand $command): never
    {
        $this->events->publishFailed(
            $command->pendingSessionId,
            $command->ipAddress,
            'invalid_code'
        );

        throw new UnauthorizedHttpException('Bearer', 'Invalid two-factor code.');
    }
}
