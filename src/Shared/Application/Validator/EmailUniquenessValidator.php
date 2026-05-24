<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use App\Shared\Application\Provider\Http\RouteIdentifierProvider;
use App\User\Application\Service\EmailNormalizer;
use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

use function trim;

final readonly class EmailUniquenessValidator
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RouteIdentifierProvider $routeIdentifierProvider,
        private EmailNormalizer $emailNormalizer
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
        return $this->emailNormalizer->normalize($email);
    }

    private function findExactUsersByEmail(string $email): UserCollection
    {
        $candidates = $this->emailLookupCandidates($email);

        if (count($candidates) !== 1) {
            return $this->userRepository->findByEmails($candidates);
        }

        $user = $this->userRepository->findByEmail($candidates[0]);

        if ($user === null) {
            return new UserCollection();
        }

        return new UserCollection([$user]);
    }

    private function findCaseInsensitiveUsersByEmail(string $email): UserCollection
    {
        return $this->userRepository->findByEmailCaseInsensitive(
            $this->normalizeEmail($email)
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
