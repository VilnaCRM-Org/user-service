<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\PasswordResetTokenMismatchException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;

final readonly class UserTokenMatchValidator
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function validateAndGetUser(
        PasswordResetTokenInterface $token,
        string $userId
    ): UserInterface {
        $user = $this->userRepository->findById($token->getUserID());

        if (!$user instanceof UserInterface) {
            throw new UserNotFoundException();
        }

        if ($user->getId() !== $userId) {
            throw new PasswordResetTokenMismatchException();
        }

        return $user;
    }
}
