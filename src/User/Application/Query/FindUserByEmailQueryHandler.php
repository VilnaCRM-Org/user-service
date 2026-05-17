<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\User\Application\Service\EmailNormalizer;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

final class FindUserByEmailQueryHandler implements
    FindUserByEmailQueryHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EmailNormalizer $emailNormalizer,
    ) {
    }

    #[\Override]
    public function find(string $email): ?UserInterface
    {
        return $this->userRepository->findByEmail(
            $this->emailNormalizer->normalize($email)
        );
    }
}
