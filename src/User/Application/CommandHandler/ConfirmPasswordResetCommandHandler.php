<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\Command\ConfirmPasswordResetCommandResponse;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordResetConfirmedEvent;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Service\PasswordResetTokenValidatorInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class ConfirmPasswordResetCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private PasswordResetTokenRepositoryInterface $tokenRepository,
        private UserRepositoryInterface $userRepository,
        private PasswordHasherFactoryInterface $hasherFactory,
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
        private PasswordResetTokenValidatorInterface $tokenValidator,
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
            new ConfirmPasswordResetCommandResponse('')
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
        return $this->userRepository->find($token->getUserId());
    }

    private function updateUserPassword(
        UserInterface $user,
        string $newPassword
    ): void {
        $hasher = $this->hasherFactory->getPasswordHasher(User::class);
        $hashedPassword = $hasher->hash($newPassword);
        $user->setPassword($hashedPassword);
        $this->userRepository->save($user);
    }

    private function markTokenAsUsed(
        PasswordResetTokenInterface $token
    ): void {
        $token->markAsUsed();
        $this->tokenRepository->save($token);
    }

    private function publishEvent(UserInterface $user): void
    {
        $this->eventBus->publish(
            new PasswordResetConfirmedEvent(
                $user->getId(),
                (string) $this->uuidFactory->create()
            )
        );
    }
}
