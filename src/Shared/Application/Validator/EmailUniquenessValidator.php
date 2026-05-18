<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use App\Shared\Application\Provider\Http\RouteIdentifierProvider;
use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use function mb_strtolower;
use function trim;

final readonly class EmailUniquenessValidator
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RouteIdentifierProvider $routeIdentifierProvider
    ) {
    }

    public function isUnique(string $email): bool
    {
        if ($this->hasConflictingUser($this->findExactUsersByEmail($email))) {
            return false;
        }

        return !$this->hasConflictingUser(
            $this->findCaseInsensitiveUsersByEmail($email)
        );
    }

    private function hasConflictingUser(UserCollection $users): bool
    {
        foreach ($users as $existingUserWithEmail) {
            if (!$this->isCurrentUserUpdatingOwnEmail($existingUserWithEmail)) {
                return true;
            }
        }

        return false;
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

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower($email, 'UTF-8');
    }

    private function findExactUsersByEmail(string $email): UserCollection
    {
        $users = [];

        foreach ($this->emailLookupCandidates($email) as $candidate) {
            $user = $this->userRepository->findByEmail($candidate);

            if ($user === null) {
                continue;
            }

            $users[] = $user;
        }

        return new UserCollection($users);
    }

    private function findCaseInsensitiveUsersByEmail(string $email): UserCollection
    {
        return $this->userRepository->findByEmailCaseInsensitive(
            $this->normalizeEmail(trim($email))
        );
    }

    /**
     * @return list<string>
     */
    private function emailLookupCandidates(string $email): array
    {
        $trimmed = trim($email);
        $normalized = $this->normalizeEmail($trimmed);

        if ($normalized === $trimmed) {
            return [$normalized];
        }

        return [$normalized, $trimmed];
    }
}
