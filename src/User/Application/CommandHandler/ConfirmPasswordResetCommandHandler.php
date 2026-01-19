<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\Command\ConfirmPasswordResetCommandResponse;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Contract\PasswordResetTokenValidatorInterface;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\Event\PasswordResetConfirmedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserUpdatedEventFactoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class ConfirmPasswordResetCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private PasswordResetTokenRepositoryInterface $tokenRepository,
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
        private PasswordResetTokenValidatorInterface $tokenValidator,
        private PasswordResetConfirmedEventFactoryInterface $eventFactory,
        private UserUpdatedEventFactoryInterface $userUpdatedEventFactory,
    ) {
    }

    public function __invoke(ConfirmPasswordResetCommand $command): void
    {
        $passwordResetToken = $this->getValidatedToken($command->token);
        $user = $this->getUserFromToken($passwordResetToken);

        $this->updateUserPassword($user, $command->newPassword);
        $this->markTokenAsUsed($passwordResetToken);
        $this->publishEvent($user);

        $command->setResponse(
            new ConfirmPasswordResetCommandResponse()
        );
    }

    private function getValidatedToken(
        string $token
    ): PasswordResetTokenInterface {
        $passwordResetToken = $this->tokenRepository->findByToken($token);
        $this->tokenValidator->validate($passwordResetToken);

        return $passwordResetToken;
    }

    private function getUserFromToken(
        PasswordResetTokenInterface $token
    ): UserInterface {
        $user = $this->userRepository->findById($token->getUserID());

        if (!$user instanceof UserInterface) {
            throw new UserNotFoundException();
        }

        return $user;
    }

    private function markTokenAsUsed(
        PasswordResetTokenInterface $token
    ): void {
        $token->markAsUsed();
        $this->tokenRepository->save($token);
    }

    private function publishEvent(UserInterface $user): void
    {
        $eventId = (string) $this->uuidFactory->create();

        $this->eventBus->publish(
            $this->eventFactory->create(
                $user->getId(),
                $eventId
            ),
            $this->userUpdatedEventFactory->create($user, null, $eventId)
        );
    }

    private function updateUserPassword(
        UserInterface $user,
        string $newPassword
    ): void {
        $hashedPassword = $this->passwordHasher->hash($newPassword);
        $user->setPassword($hashedPassword);
        $this->userRepository->save($user);
    }
}
