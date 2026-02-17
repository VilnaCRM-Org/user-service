<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\SignOutCommand;
use App\User\Domain\Event\SessionRevokedEvent;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;

/**
 * @implements CommandHandlerInterface<SignOutCommand, void>
 */
final readonly class SignOutCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private AuthSessionRepositoryInterface $sessionRepository,
        private AuthRefreshTokenRepositoryInterface $refreshTokenRepository,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(SignOutCommand $command): void
    {
        // AC: FR-13 - Revoke current session
        $session = $this->sessionRepository->findById($command->sessionId);
        if ($session !== null) {
            $session->revoke();
            $this->sessionRepository->save($session);
        }

        // AC: FR-13 - Revoke all refresh tokens for this session
        $this->refreshTokenRepository->revokeBySessionId($command->sessionId);

        // AC: NFR-33 - Emit audit event
        $this->eventBus->publish(new SessionRevokedEvent(
            $command->userId,
            $command->sessionId,
            'logout'
        ));
    }
}
