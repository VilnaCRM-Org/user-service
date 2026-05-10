<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;

final class GetUserQueryHandler implements GetUserQueryHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    #[\Override]
    public function handle(string $id): User
    {
        return $this->userRepository->findById($id)
            ?? throw new UserNotFoundException();
    }
}
