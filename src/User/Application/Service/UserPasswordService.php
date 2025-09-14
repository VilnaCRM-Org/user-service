<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final readonly class UserPasswordService
{
    public function __construct(
        private PasswordHasherFactoryInterface $hasherFactory,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function updateUserPassword(UserInterface $user, string $newPassword): void
    {
        $hasher = $this->hasherFactory->getPasswordHasher(User::class);
        $hashedPassword = $hasher->hash($newPassword);
        $user->setPassword($hashedPassword);
        $this->userRepository->save($user);
    }

    public function hashPassword(string $password): string
    {
        $hasher = $this->hasherFactory->getPasswordHasher(User::class);
        return $hasher->hash($password);
    }
}