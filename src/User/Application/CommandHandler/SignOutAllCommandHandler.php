<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\SignOutAllCommand;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @implements CommandHandlerInterface<SignOutAllCommand, void>
 */
final readonly class SignOutAllCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private AuthSessionRepositoryInterface $sessionRepository,
        private AuthRefreshTokenRepositoryInterface $refreshTokenRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(SignOutAllCommand $command): void
    {
        // AC: FR-14 - Revoke all sessions for user
        $sessions = $this->sessionRepository->findByUserId($command->userId);

        $revokedCount = 0;
        foreach ($sessions as $session) {
            if (!$session->isRevoked()) {
                $session->revoke();
                $this->sessionRepository->save($session);

                // Revoke all refresh tokens for this session
                $this->refreshTokenRepository->revokeBySessionId($session->getId());

                ++$revokedCount;
            }
        }

        // AC: NFR-33 - Emit audit event
        $this->eventDispatcher->dispatch(new AllSessionsRevokedEvent(
            $command->userId,
            'user_initiated',
            $revokedCount,
            uniqid('event_', true),
            null
        ));
    }
}
