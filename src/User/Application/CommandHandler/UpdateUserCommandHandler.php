<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\InvalidPasswordException;
use App\User\Domain\Factory\Event\EmailChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserUpdatedEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserUpdate;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class UpdateUserCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
        private PasswordHasherFactoryInterface $hasherFactory,
        private UserRepositoryInterface $userRepository,
        private UuidFactory $uuidFactory,
        private EmailChangedEventFactoryInterface $emailChangedEventFactory,
        private PasswordChangedEventFactoryInterface $passwordChangedFactory,
        private UserUpdatedEventFactoryInterface $userUpdatedEventFactory,
    ) {
    }

    public function __invoke(UpdateUserCommand $command): void
    {
        $user = $command->user;
        $hasher = $this->hasherFactory->getPasswordHasher(User::class);

        $this->assertPasswordValid(
            $hasher,
            $user,
            $command->updateData->oldPassword
        );

        $eventId = (string) $this->uuidFactory->create();
        $previousEmail = $user->getEmail();
        $hashedPassword = $hasher->hash($command->updateData->newPassword);

        $events = $this->applyUpdate(
            $user,
            $command->updateData,
            $hashedPassword,
            $eventId,
            $previousEmail
        );
        $this->eventBus->publish(...$events);
    }

    private function assertPasswordValid(
        PasswordHasherInterface $hasher,
        User $user,
        string $oldPassword
    ): void {
        if ($hasher->verify($user->getPassword(), $oldPassword)) {
            return;
        }

        throw new InvalidPasswordException();
    }

    /**
     * @return array<int, object>
     */
    private function applyUpdate(
        User $user,
        UserUpdate $updateData,
        string $hashedPassword,
        string $eventId,
        string $previousEmail
    ): array {
        $events = $user->update(
            $updateData,
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
}
