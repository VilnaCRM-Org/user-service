<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;

use function strtolower;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

use function trim;

final readonly class PasskeyUserResolver
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function findUserIdByEmail(string $email): ?string
    {
        $user = $this->userRepository->findByEmail($this->normalizeEmail($email));

        return $user instanceof User ? $user->getId() : null;
    }

    public function assertEmailIsAvailable(string $email): void
    {
        if ($this->userRepository->findByEmail($this->normalizeEmail($email)) !== null) {
            throw new ConflictHttpException('Email is already registered.');
        }
    }

    public function save(User $user): void
    {
        $this->userRepository->save($user);
    }

    public function resolveAuthenticated(string $userId): User
    {
        $user = $this->userRepository->findById($userId);

        if (!$user instanceof User) {
            throw new UnauthorizedHttpException('Bearer', 'Authentication required.');
        }

        return $user;
    }

    public function resolveCredentialOwner(string $userId): User
    {
        $user = $this->userRepository->findById($userId);

        if (!$user instanceof User) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid passkey credential.');
        }

        return $user;
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
