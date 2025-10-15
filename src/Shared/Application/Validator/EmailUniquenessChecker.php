<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use App\Shared\Application\Http\RouteIdentifierProvider;
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
        $identifier = $this->routeIdentifierProvider->identifier('id');

        return match (true) {
            !$existingUser instanceof UserInterface => true,
            $identifier === null => false,
            default => $this->normalize($identifier)
                === $this->normalize($existingUser->getId()),
        };
    }

    private function normalize(string $value): string
    {
        return strtolower(str_replace('-', '', $value));
    }
}
