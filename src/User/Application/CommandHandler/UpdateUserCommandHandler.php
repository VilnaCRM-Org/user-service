<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Applier\UserUpdateApplierInterface;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Application\Generator\EventIdGeneratorInterface;
use App\User\Application\Hasher\PasswordHasherInterface;
use App\User\Application\Revoker\PasswordChangeSessionRevokerInterface;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Exception\InvalidPasswordException;

final readonly class UpdateUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
        private PasswordHasherInterface $passwordHasher,
        private UserUpdateApplierInterface $userUpdateApplier,
        private PasswordChangeSessionRevokerInterface $passwordChangeSessionRevoker,
        private EventIdGeneratorInterface $eventIdGenerator,
    ) {
    }

    public function __invoke(UpdateUserCommand $command): void
    {
        $user = $command->user;
        $this->assertPasswordIsValid($user->getPassword(), $command->updateData->oldPassword);

        $eventId = $this->eventIdGenerator->generate();
        $events = $this->userUpdateApplier->apply(
            $user,
            $command->updateData,
            $this->passwordHasher->hash($command->updateData->newPassword),
            $eventId
        );

        $finalEvents = $this->appendRevocationEvent($command, $user->getId(), $events, $eventId);
        $this->eventBus->publish(...$finalEvents);
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

        $revokedCount = $this->passwordChangeSessionRevoker->revokeOtherSessions(
            $userId,
            $command->currentSessionId
        );
        $events[] = new AllSessionsRevokedEvent(
            $userId,
            'password_change',
            $revokedCount,
            $eventId
        );

        return $events;
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
