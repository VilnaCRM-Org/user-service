<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Domain\Factory\PasswordResetTokenFactoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

final readonly class RequestPasswordResetCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordResetTokenFactoryInterface $passwordResetTokenFactory,
        private PasswordResetTokenRepositoryInterface $passwordResetTokenRepository
    ) {
    }

    public function __invoke(RequestPasswordResetCommand $command): void
    {
        $user = $this->userRepository->findByEmail($command->email);
        
        // For security reasons, we don't reveal if the email exists or not
        // We silently ignore requests for non-existent emails
        if ($user === null) {
            return;
        }

        // Create password reset token
        $token = $this->passwordResetTokenFactory->create($user->getId());
        
        // Save the token
        $this->passwordResetTokenRepository->save($token);
        
        // TODO: Send password reset email
        // This would typically involve dispatching an event or sending a message
        // For now, we just store the token
    }
}