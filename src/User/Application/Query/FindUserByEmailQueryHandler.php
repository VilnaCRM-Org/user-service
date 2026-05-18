<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\User\Application\Service\EmailNormalizer;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\DuplicateEmailException;
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
        $normalizedEmail = $this->emailNormalizer->normalize($email);
        $exactUser = $this->userRepository->findByEmail($normalizedEmail);
        $caseInsensitiveUsers =
            $this->userRepository->findByEmailCaseInsensitive($normalizedEmail);

        if ($caseInsensitiveUsers->count() > 1) {
            throw new DuplicateEmailException($normalizedEmail);
        }

        if ($exactUser !== null) {
            return $exactUser;
        }

        foreach ($caseInsensitiveUsers as $user) {
            return $user;
        }

        return null;
    }
}
