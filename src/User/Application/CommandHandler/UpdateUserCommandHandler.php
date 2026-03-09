<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Application\Generator\EventIdGeneratorInterface;
use App\User\Application\Hasher\PasswordHasherInterface;
use App\User\Application\Revoker\PasswordChangeSessionRevokerInterface;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Exception\InvalidPasswordException;
use App\User\Domain\Factory\Event\EmailChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserUpdatedEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

final readonly class UpdateUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
        private PasswordHasherInterface $passwordHasher,
        private PasswordChangeSessionRevokerInterface $passwordChangeSessionRevoker,
        private EventIdGeneratorInterface $eventIdGenerator,
        private UserRepositoryInterface $userRepository,
        private EmailChangedEventFactoryInterface $emailChangedEventFactory,
        private PasswordChangedEventFactoryInterface $passwordChangedFactory,
        private UserUpdatedEventFactoryInterface $userUpdatedEventFactory,
    ) {
    }

    public function __invoke(UpdateUserCommand $command): void
    {
        $user = $command->user;
        $this->assertPasswordIsValid($user->getPassword(), $command->updateData->oldPassword);

        $eventId = $this->eventIdGenerator->generate();
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
            $this->emailChangedEventFactory,
            $this->passwordChangedFactory
        );

        $this->userRepository->save($user);

        $events[] = $this->userUpdatedEventFactory->create(
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
