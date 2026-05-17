<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

use function mb_strtolower;
use function trim;

final class FindUserByEmailQueryHandler implements
    FindUserByEmailQueryHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    #[\Override]
    public function find(string $email): ?UserInterface
    {
        return $this->userRepository->findByEmail($this->normalizeEmail($email));
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
