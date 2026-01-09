<?php

declare(strict_types=1);

namespace App\Shared\Application\Checker;

use App\Shared\Application\Provider\Http\RouteIdentifierProvider;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

final readonly class EmailUniquenessChecker
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RouteIdentifierProvider $routeIdentifierProvider
    ) {
    }

    public function isUnique(string $email): bool
    {
        $existingUser = $this->userRepository->findByEmail($email);

        if (!$existingUser instanceof UserInterface) {
            return true;
        }

        return $this->isUpdatingSameUser($existingUser);
    }

    private function isUpdatingSameUser(UserInterface $existingUser): bool
    {
        $routeUserId = $this->routeIdentifierProvider->identifier('id');

        if ($routeUserId === null) {
            return false;
        }

        $normalizedRouteId = $this->normalizeUuid($routeUserId);
        $normalizedExistingId = $this->normalizeUuid($existingUser->getId());

        return $normalizedRouteId === $normalizedExistingId;
    }

    private function normalizeUuid(string $uuid): string
    {
        return strtolower(str_replace('-', '', $uuid));
    }
}
