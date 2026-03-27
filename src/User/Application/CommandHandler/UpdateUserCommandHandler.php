<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Application\Factory\EventIdFactoryInterface;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Exception\InvalidPasswordException;
use App\User\Domain\Factory\Event\SessionRevocationEventFactoryInterface;
use App\User\Domain\Factory\Event\UserUpdateEventFactoryInterface;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

final readonly class UpdateUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
        private PasswordHasherInterface $passwordHasher,
        private AuthSessionRepositoryInterface $authSessionRepository,
        private AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository,
        private EventIdFactoryInterface $eventIdFactory,
        private UserRepositoryInterface $userRepository,
        private SessionRevocationEventFactoryInterface $sessionRevocationEventFactory,
        private UserUpdateEventFactoryInterface $userUpdateEventFactory,
    ) {
    }

    public function __invoke(UpdateUserCommand $command): void
    {
        $user = $command->user;
        $this->assertPasswordIsValid($user->getPassword(), $command->updateData->oldPassword);

        $eventId = $this->eventIdFactory->generate();
        $events = $this->applyUpdate($command, $eventId);

        $finalEvents = $this->appendRevocationEvent($command, $user->getId(), $events, $eventId);
        $this->eventBus->publish(...$finalEvents);
    }

    /**
     * @return list<DomainEvent>
     */
    private function applyUpdate(UpdateUserCommand $command, string $eventId): array
    {
        $user = $command->user;
        $previousEmail = $user->getEmail();
        $hashedPassword = $this->passwordHasher->hash($command->updateData->newPassword);

        $events = $user->update(
            $command->updateData,
            $hashedPassword,
            $eventId,
            $this->userUpdateEventFactory
        );

        $this->userRepository->save($user);

        $events[] = $this->userUpdateEventFactory->createUserUpdated(
            $user,
            $previousEmail !== $user->getEmail() ? $previousEmail : null,
            $eventId
        );

        return $events;
    }

    /**
     * @param list<\App\Shared\Domain\Bus\Event\DomainEvent> $events
     *
     * @return list<\App\Shared\Domain\Bus\Event\DomainEvent>
     */
    private function appendRevocationEvent(
        UpdateUserCommand $command,
        string $userId,
        array $events,
        string $eventId
    ): array {
        if ($command->updateData->newPassword === $command->updateData->oldPassword) {
            return $events;
        }

        $revokedCount = $this->revokeOtherSessions(
            $userId,
            $command->currentSessionId
        );
        $events[] = $this->sessionRevocationEventFactory->createAllSessionsRevoked(
            $userId,
            'password_change',
            $revokedCount,
            $eventId
        );

        return $events;
    }

    private function revokeOtherSessions(string $userId, string $currentSessionId): int
    {
        $sessions = $this->authSessionRepository->findByUserId($userId);
        $revokedCount = 0;

        foreach ($sessions as $session) {
            if ($session->isRevoked() || $session->getId() === $currentSessionId) {
                continue;
            }

            $session->revoke();
            $this->authSessionRepository->save($session);
            $this->revokeActiveRefreshTokens($session->getId());

            ++$revokedCount;
        }

        return $revokedCount;
    }

    private function revokeActiveRefreshTokens(string $sessionId): void
    {
        foreach ($this->authRefreshTokenRepository->findBySessionId($sessionId) as $refreshToken) {
            if ($refreshToken->isRevoked()) {
                continue;
            }

            $refreshToken->revoke();
            $this->authRefreshTokenRepository->save($refreshToken);
        }
    }

    private function assertPasswordIsValid(
        string $currentPasswordHash,
        string $oldPassword
    ): void {
        if (!$this->passwordHasher->verify($currentPasswordHash, $oldPassword)) {
            throw new InvalidPasswordException();
        }
    }
}
