<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;

final class FindUserByEmailQueryHandler implements
    FindUserByEmailQueryHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    #[\Override]
    public function find(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }
}
