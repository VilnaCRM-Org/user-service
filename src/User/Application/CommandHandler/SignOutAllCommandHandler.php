<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\SignOutAllCommand;
use App\User\Application\Processor\EventPublisher\SessionEventsInterface;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;

/**
 * @implements CommandHandlerInterface<SignOutAllCommand, void>
 */
final readonly class SignOutAllCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private AuthSessionRepositoryInterface $sessionRepository,
        private AuthRefreshTokenRepositoryInterface $refreshTokenRepository,
        private SessionEventsInterface $sessionEvents,
    ) {
    }

    public function __invoke(SignOutAllCommand $command): void
    {
        $sessions = $this->sessionRepository->findByUserId($command->userId);

        $revokedCount = 0;
        foreach ($sessions as $session) {
            $this->refreshTokenRepository->revokeBySessionId($session->getId());

            if (!$session->isRevoked()) {
                $session->revoke();
                $this->sessionRepository->save($session);

                ++$revokedCount;
            }
        }

        $this->sessionEvents->publishAllSessionsRevoked(
            $command->userId,
            'user_initiated',
            $revokedCount
        );
    }
}
