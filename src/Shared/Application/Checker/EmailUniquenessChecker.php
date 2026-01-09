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
        $existingUserWithEmail = $this->userRepository->findByEmail($email);

        $emailNotUsed = !$existingUserWithEmail instanceof UserInterface;
        if ($emailNotUsed) {
            return true;
        }

        return $this->isCurrentUserUpdatingOwnEmail($existingUserWithEmail);
    }

    private function isCurrentUserUpdatingOwnEmail(
        UserInterface $userWithEmail
    ): bool {
        $currentUserId = $this->getCurrentUserIdFromRoute();
        if ($currentUserId === null) {
            return false;
        }

        return $this->isSameUser($currentUserId, $userWithEmail->getId());
    }

    private function getCurrentUserIdFromRoute(): ?string
    {
        return $this->routeIdentifierProvider->identifier('id');
    }

    private function isSameUser(string $firstId, string $secondId): bool
    {
        return $this->normalizeUuid($firstId) === $this->normalizeUuid($secondId);
    }

    private function normalizeUuid(string $uuid): string
    {
        return strtolower(str_replace('-', '', $uuid));
    }
}
