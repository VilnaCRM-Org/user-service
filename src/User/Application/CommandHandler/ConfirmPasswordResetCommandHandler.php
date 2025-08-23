<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\Query\GetUserQueryHandler;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\TokenExpiredException;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final readonly class ConfirmPasswordResetCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private GetUserQueryHandler $getUserQueryHandler,
        private UserRepositoryInterface $userRepository,
        private PasswordResetTokenRepositoryInterface $passwordResetTokenRepository,
        private PasswordHasherFactoryInterface $hasherFactory
    ) {
    }

    public function __invoke(ConfirmPasswordResetCommand $command): void
    {
        $token = $command->token;

        // Check if token is expired
        if ($token->isExpired()) {
            throw new TokenExpiredException();
        }

        // Get the user
        $user = $this->getUserQueryHandler->handle(
            $token->getUserID()
        );

        // Hash the new password
        $hasher = $this->hasherFactory->getPasswordHasher(User::class);
        $hashedPassword = $hasher->hash($command->newPassword);
        
        // Update user's password
        $user->setPassword($hashedPassword);
        
        // Save the user
        $this->userRepository->save($user);
        
        // Delete the used token
        $this->passwordResetTokenRepository->delete($token);
    }
}