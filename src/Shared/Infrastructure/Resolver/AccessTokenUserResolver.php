<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Resolver;

use App\Shared\Infrastructure\Adapter\ServicePrincipal;
use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Application\Transformer\UserTransformer;
use App\User\Domain\Entity\UserInterface as DomainUserInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

final readonly class AccessTokenUserResolver
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserTransformer $userTransformer,
        private AuthSessionRepositoryInterface $authSessionRepository,
    ) {
    }

    /**
     * @param array<string> $roles
     */
    public function resolve(
        string $subject,
        array $roles,
        string $sid
    ): AuthorizationUserDto|ServicePrincipal {
        if (in_array('ROLE_SERVICE', $roles, true)) {
            return new ServicePrincipal($subject, $roles);
        }

        $this->validateSession($sid);

        $user = $this->userRepository->findByEmail($subject);
        if ($user instanceof DomainUserInterface) {
            return $this->userTransformer->transformToAuthorizationUser($user);
        }

        if ($this->looksLikeUuid($subject)) {
            $user = $this->userRepository->findById($subject);
            if ($user instanceof DomainUserInterface) {
                return $this->userTransformer->transformToAuthorizationUser($user);
            }
        }

        throw new CustomUserMessageAuthenticationException('Authentication required.');
    }

    private function validateSession(string $sid): void
    {
        $session = $this->authSessionRepository->findById($sid);
        if ($session === null || $session->isRevoked() || $session->isExpired()) {
            throw new CustomUserMessageAuthenticationException('Invalid access token claims.');
        }
    }

    private function looksLikeUuid(string $subject): bool
    {
        return preg_match(
            '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-8][0-9a-fA-F]{3}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
            $subject
        ) === 1;
    }
}
