<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class UpdateUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
        private PasswordHasherFactoryInterface $hasherFactory,
        private UserUpdateApplier $userUpdateApplier,
        private PasswordChangeSessionRevoker $passwordChangeSessionRevoker,
        private UuidFactory $uuidFactory,
    ) {
    }

    public function __invoke(UpdateUserCommand $command): void
    {
        $user = $command->user;
        $hasher = $this->hasherFactory->getPasswordHasher($user::class);

        $this->assertPasswordValid(
            $hasher,
            $user,
            $command->updateData->oldPassword
        );

        $events = $this->resolveEvents($command, $user, $hasher);
        $this->eventBus->publish(...$events);
    }

    /**
     * @return \App\Shared\Domain\Bus\Event\DomainEvent[]
     *
     * @psalm-return array<int, \App\Shared\Domain\Bus\Event\DomainEvent>
     */
    private function resolveEvents(
        UpdateUserCommand $command,
        UserInterface $user,
        object $hasher
    ): array {
        $eventId = (string) $this->uuidFactory->create();
        $events = $this->userUpdateApplier->apply(
            $user,
            $command->updateData,
            $hasher->hash($command->updateData->newPassword),
            $eventId
        );

        if (!$this->passwordChanged($command->updateData)) {
            return $events;
        }

        $events[] = $this->revokeOtherSessionsAndTokens(
            $user->getId(),
            $command->currentSessionId,
            $eventId
        );

        return $events;
    }

    private function assertPasswordValid(
        object $hasher,
        UserInterface $user,
        string $oldPassword
    ): void {
        if ($hasher->verify($user->getPassword(), $oldPassword)) {
            return;
        }

        throw new InvalidPasswordException();
    }

    private function passwordChanged(object $updateData): bool
    {
        return $updateData->newPassword !== $updateData->oldPassword;
    }

    private function revokeOtherSessionsAndTokens(
        string $userId,
        string $currentSessionId,
        string $eventId
    ): AllSessionsRevokedEvent {
        $revokedCount = $this->passwordChangeSessionRevoker->revokeOtherSessions(
            $userId,
            $currentSessionId
        );

        return new AllSessionsRevokedEvent(
            $userId,
            'password_change',
            $revokedCount,
            $eventId
        );
    }
}
