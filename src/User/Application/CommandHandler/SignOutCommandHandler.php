<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\SignOutCommand;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Infrastructure\Publisher\SessionPublisherInterface;

/**
 * @implements CommandHandlerInterface<SignOutCommand, void>
 */
final readonly class SignOutCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private AuthSessionRepositoryInterface $sessionRepository,
        private AuthRefreshTokenRepositoryInterface $refreshTokenRepository,
        private SessionPublisherInterface $sessionEvents,
    ) {
    }

    public function __invoke(SignOutCommand $command): void
    {
        $session = $this->sessionRepository->findById($command->sessionId);
        if ($session !== null) {
            $session->revoke();
            $this->sessionRepository->save($session);

            $this->sessionEvents->publishSessionRevoked(
                $command->userId,
                $command->sessionId,
                'logout'
            );
        }

        $this->refreshTokenRepository->revokeBySessionId($command->sessionId);
    }
}
