<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\Command\ConfirmPasswordResetCommandResponse;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordResetConfirmedEvent;
use App\User\Domain\Exception\PasswordResetTokenAlreadyUsedException;
use App\User\Domain\Exception\PasswordResetTokenExpiredException;
use App\User\Domain\Exception\PasswordResetTokenNotFoundException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class ConfirmPasswordResetCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordResetTokenRepositoryInterface $passwordResetTokenRepository,
        private PasswordHasherFactoryInterface $hasherFactory,
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
    ) {
    }

    public function __invoke(ConfirmPasswordResetCommand $command): void
    {
        // Find the password reset token
        $passwordResetToken = $this->passwordResetTokenRepository->findByToken($command->token);

        if (!$passwordResetToken) {
            throw new PasswordResetTokenNotFoundException();
        }

        // Check if token is expired
        if ($passwordResetToken->isExpired()) {
            throw new PasswordResetTokenExpiredException();
        }

        // Check if token is already used
        if ($passwordResetToken->isUsed()) {
            throw new PasswordResetTokenAlreadyUsedException();
        }

        // Find the user
        $user = $this->userRepository->findById($passwordResetToken->getUserID());

        if (!$user instanceof UserInterface) {
            throw new UserNotFoundException();
        }

        // Hash the new password
        $hasher = $this->hasherFactory->getPasswordHasher(User::class);
        $hashedPassword = $hasher->hash($command->newPassword);
        $user->setPassword($hashedPassword);

        // Mark token as used
        $passwordResetToken->markAsUsed();

        // Save changes
        $this->userRepository->save($user);
        $this->passwordResetTokenRepository->save($passwordResetToken);

        // Publish event
        $this->eventBus->publish(
            new PasswordResetConfirmedEvent(
                $user,
                (string) $this->uuidFactory->create()
            )
        );

        $command->setResponse(new ConfirmPasswordResetCommandResponse('Password has been reset successfully.'));
    }
}
