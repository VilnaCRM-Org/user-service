<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Application\Component\PasswordChangeSessionRevoker;
use App\User\Application\Component\UserUpdateApplierInterface;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Event\AllSessionsRevokedEvent;
use App\User\Domain\Exception\InvalidPasswordException;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class UpdateUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
        private PasswordHasherInterface $passwordHasher,
        private UserUpdateApplierInterface $userUpdateApplier,
        private PasswordChangeSessionRevoker $passwordChangeSessionRevoker,
        private UuidFactory $uuidFactory,
    ) {
    }

    public function __invoke(UpdateUserCommand $command): void
    {
        $user = $command->user;
        $this->assertPasswordIsValid($user->getPassword(), $command->updateData->oldPassword);

        $eventId = (string) $this->uuidFactory->create();
        $events = $this->userUpdateApplier->apply(
            $user,
            $command->updateData,
            $this->passwordHasher->hash($command->updateData->newPassword),
            $eventId
        );

        if ($command->updateData->newPassword !== $command->updateData->oldPassword) {
            $events[] = $this->buildPasswordChangeRevocationEvent(
                $user->getId(),
                $command->currentSessionId,
                $eventId
            );
        }

        $this->eventBus->publish(...$events);
    }

    private function assertPasswordIsValid(
        string $currentPasswordHash,
        string $oldPassword
    ): void {
        if (!$this->passwordHasher->verify($currentPasswordHash, $oldPassword)) {
            throw new InvalidPasswordException();
        }
    }

    private function buildPasswordChangeRevocationEvent(
        string $userId,
        string $currentSessionId,
        string $eventId
    ): AllSessionsRevokedEvent {
        return new AllSessionsRevokedEvent(
            $userId,
            'password_change',
            $this->passwordChangeSessionRevoker->revokeOtherSessions(
                $userId,
                $currentSessionId
            ),
            $eventId
        );
    }
}
