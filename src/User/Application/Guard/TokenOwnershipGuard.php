<?php

declare(strict_types=1);

namespace App\User\Application\Guard;

use App\User\Application\DTO\AuthorizationUserDto;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class TokenOwnershipGuard implements OwnershipGuardInterface
{
    public function __construct(private TokenStorageInterface $tokenStorage)
    {
    }

    #[\Override]
    public function assertOwnership(string $resourceUserId): void
    {
        $token = $this->tokenStorage->getToken();
        $authenticatedUser = $token?->getUser();

        if (!$authenticatedUser instanceof AuthorizationUserDto) {
            throw new AccessDeniedException('Access Denied.');
        }

        if ($authenticatedUser->getId()->__toString() !== $resourceUserId) {
            throw new AccessDeniedException('Access Denied.');
        }
    }
}
