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
use App\User\Domain\Exception\PasswordResetTokenAlreadyUsedException;
use App\User\Domain\Exception\PasswordResetTokenExpiredException;
use App\User\Domain\Exception\PasswordResetTokenMismatchException;
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
        private PasswordResetTokenRepositoryInterface $tokenRepository,
        private PasswordHasherFactoryInterface $hasherFactory,
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
    ) {
    }

    public function __invoke(ConfirmPasswordResetCommand $command): void
    {
        $passwordResetToken = $this->validatePasswordResetToken(
            $command->token
        );
        $user = $this->findUserByToken($passwordResetToken);
        $this->validateUserTokenMatch($user, $command->userId);

        $this->updateUserPassword($user, $command->newPassword);
        $this->markTokenAsUsed($passwordResetToken);
        $this->publishEvent($user);

        $command->setResponse(
            new ConfirmPasswordResetCommandResponse(
                'Password has been reset successfully.'
            )
        );
    }

    private function validatePasswordResetToken(
        string $token
    ): PasswordResetTokenInterface {
        $passwordResetToken = $this->tokenRepository->findByToken($token);

        $this->ensureTokenExists($passwordResetToken);
        $this->ensureTokenNotExpired($passwordResetToken);
        $this->ensureTokenNotUsed($passwordResetToken);

        return $passwordResetToken;
    }

    private function ensureTokenExists(
        ?PasswordResetTokenInterface $token
    ): void {
        if (!$token) {
            throw new PasswordResetTokenNotFoundException();
        }
    }

    private function ensureTokenNotExpired(
        PasswordResetTokenInterface $token
    ): void {
        if ($token->isExpired()) {
            throw new PasswordResetTokenExpiredException();
        }
    }

    private function ensureTokenNotUsed(
        PasswordResetTokenInterface $token
    ): void {
        if ($token->isUsed()) {
            throw new PasswordResetTokenAlreadyUsedException();
        }
    }

    private function findUserByToken(
        PasswordResetTokenInterface $token
    ): UserInterface {
        $user = $this->userRepository->findById($token->getUserID());

        if (!$user instanceof UserInterface) {
            throw new UserNotFoundException();
        }

        return $user;
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

    private function validateUserTokenMatch(
        UserInterface $user,
        string $userId
    ): void {
        if ($user->getId() !== $userId) {
            throw new PasswordResetTokenMismatchException();
        }
    }
}
