<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;

final class GetUserQueryHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function handle(string $id): User
    {
        return $this->userRepository->find($id)
            ?? throw new UserNotFoundException();
    }
}
