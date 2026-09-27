<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Application\Validator\PasswordResetTokenValidatorInterface;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

final readonly class PasswordResetConfirmationService
{
    public function __construct(
        private PasswordResetTokenRepositoryInterface $tokenRepository,
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private PasswordResetTokenValidatorInterface $tokenValidator,
    ) {
    }

    public function confirm(string $token, string $newPassword): UserInterface
    {
        $passwordResetToken = $this->getValidatedToken($token);
        $user = $this->getUserFromToken($passwordResetToken);

        $this->updateUserPassword($user, $newPassword);
        $this->markTokenAsUsed($passwordResetToken);

        return $user;
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

    private function updateUserPassword(
        UserInterface $user,
        string $newPassword
    ): void {
        $hashedPassword = $this->passwordHasher->hash($newPassword);
        $user->setPassword($hashedPassword);
        $this->userRepository->save($user);
    }
}
