<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\Command\SignOutAllCommand;
use App\User\Application\DTO\ConfirmPasswordResetCommandResponse;
use App\User\Application\Provider\AccountLockoutProviderInterface;
use App\User\Application\Validator\PasswordResetTokenValidatorInterface;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Entity\PasswordResetTokenInterface;
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
        private AccountLockoutProviderInterface $accountLockoutGuard,
        private CommandBusInterface $commandBus,
        private PasswordResetConfirmationPublisherInterface $publisher,
    ) {
    }

    public function __invoke(
        ConfirmPasswordResetCommand $command
    ): ConfirmPasswordResetCommandResponse {
        $passwordResetToken = $this->getValidatedToken($command->token);
        $user = $this->userRepository->findById($passwordResetToken->getUserID());

        if ($user === null) {
            throw new UserNotFoundException();
        }

        $hashedPassword = $this->passwordHasher->hash($command->newPassword);
        $user->setPassword($hashedPassword);
        $this->userRepository->save($user);
        $this->markTokenAsUsed($passwordResetToken);
        $this->accountLockoutGuard->clearFailures(
            strtolower(trim($user->getEmail()))
        );
        $this->commandBus->dispatch(
            new SignOutAllCommand($user->getId(), 'password_reset')
        );
        $this->publisher->publish($user);

        return new ConfirmPasswordResetCommandResponse();
    }

    private function getValidatedToken(
        string $token
    ): PasswordResetTokenInterface {
        $passwordResetToken = $this->tokenRepository->findByToken($token);
        $this->tokenValidator->validate($passwordResetToken);
        assert($passwordResetToken instanceof PasswordResetTokenInterface);

        return $passwordResetToken;
    }

    private function markTokenAsUsed(
        PasswordResetTokenInterface $token
    ): void {
        $token->markAsUsed();
        $this->tokenRepository->save($token);
    }
}
