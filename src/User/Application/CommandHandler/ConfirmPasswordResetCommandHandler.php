<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\Command\SignOutAllCommand;
use App\User\Application\PasswordHasherInterface;
use App\User\Application\Validator\AccountLockoutValidatorInterface;
use App\User\Application\Validator\PasswordResetTokenValidatorInterface;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Publisher\PasswordResetConfirmationPublisherInterface;

final readonly class ConfirmPasswordResetCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private PasswordResetTokenRepositoryInterface $tokenRepository,
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private PasswordResetTokenValidatorInterface $tokenValidator,
        private AccountLockoutValidatorInterface $accountLockoutGuard,
        private CommandBusInterface $commandBus,
        private PasswordResetConfirmationPublisherInterface $publisher,
    ) {
    }

    public function __invoke(ConfirmPasswordResetCommand $command): void
    {
        $passwordResetToken = $this->getValidatedToken($command->token);
        $user = $this->getUserFromToken($passwordResetToken);

        $this->updateUserPassword($user, $command->newPassword);
        $this->markTokenAsUsed($passwordResetToken);
        $this->accountLockoutGuard->clearFailures(
            strtolower(trim($user->getEmail()))
        );
        $this->commandBus->dispatch(
            new SignOutAllCommand($user->getId(), 'password_reset')
        );
        $this->publishEvent($user);

        $command->markCompleted();
    }

    private function getValidatedToken(
        string $token
    ): PasswordResetTokenInterface {
        $passwordResetToken = $this->tokenRepository->findByToken($token);
        $this->tokenValidator->validate($passwordResetToken);
        assert($passwordResetToken instanceof PasswordResetTokenInterface);

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
        $this->publisher->publish($user);
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
