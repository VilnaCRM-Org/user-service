<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\SignOutAllCommand;
use App\User\Domain\Event\AllSessionsRevokedEvent;
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
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(SignOutAllCommand $command): void
    {
        // AC: FR-14 - Revoke all sessions for user
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

        // AC: NFR-33 - Emit audit event
        $this->eventBus->publish(new AllSessionsRevokedEvent(
            $command->userId,
            'user_initiated',
            $revokedCount,
            uniqid('event_', true),
            null
        ));
    }
}
