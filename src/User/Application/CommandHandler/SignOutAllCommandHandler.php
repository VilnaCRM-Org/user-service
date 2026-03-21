<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\SignOutAllCommand;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Infrastructure\Publisher\SessionPublisherInterface;

/**
 * @implements CommandHandlerInterface<SignOutAllCommand, void>
 */
final readonly class SignOutAllCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private AuthSessionRepositoryInterface $sessionRepository,
        private AuthRefreshTokenRepositoryInterface $refreshTokenRepository,
        private SessionPublisherInterface $sessionEvents,
    ) {
    }

    public function __invoke(SignOutAllCommand $command): void
    {
        $this->revokeAllSessions(
            $command->userId,
            $command->reason,
        );
    }

    private function revokeAllSessions(string $userId, string $reason): void
    {
        $sessions = $this->sessionRepository->findByUserId($userId);
        $revokedCount = 0;

        foreach ($sessions as $session) {
            $this->refreshTokenRepository->revokeBySessionId($session->getId());

            if ($session->isRevoked()) {
                continue;
            }

            $session->revoke();
            $this->sessionRepository->save($session);
            ++$revokedCount;
        }

        $this->sessionEvents->publishAllSessionsRevoked(
            $userId,
            $reason,
            $revokedCount
        );
    }
}
