<?php

declare(strict_types=1);

namespace App\User\Application\Validator;

use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception as DomainException;
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

        return match (true) {
            !$user instanceof UserInterface => $this->throwUserNotFound(),
            $user->getId() !== $userId => $this->throwTokenMismatch(),
            default => $user,
        };
    }

    private function throwUserNotFound(): never
    {
        throw new DomainException\UserNotFoundException();
    }

    private function throwTokenMismatch(): never
    {
        throw new DomainException\PasswordResetTokenMismatchException();
    }
}
