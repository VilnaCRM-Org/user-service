<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use App\Shared\Application\Provider\Http\RouteIdentifierProvider;
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
        foreach ($this->emailLookupCandidates($email) as $candidate) {
            $existingUserWithEmail = $this->userRepository->findByEmail($candidate);

            if (!$existingUserWithEmail instanceof UserInterface) {
                continue;
            }

            if (!$this->isCurrentUserUpdatingOwnEmail($existingUserWithEmail)) {
                return false;
            }
        }

        return true;
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
        return mb_strtolower($email);
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
