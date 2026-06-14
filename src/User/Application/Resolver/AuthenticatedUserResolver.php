<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use App\User\Application\Query\FindUserByEmailQueryHandlerInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\DuplicateEmailException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Resolves the authenticated user from an email for authenticated 2FA flows.
 *
 * Centralizes the auth-deny behavior so every 2FA handler treats a missing
 * user and an ambiguous (duplicate) email identically, instead of leaking a
 * duplicate-email lookup as an HTTP 500.
 */
final readonly class AuthenticatedUserResolver
{
    public function __construct(
        private FindUserByEmailQueryHandlerInterface $findUserByEmailQueryHandler,
    ) {
    }

    public function resolve(string $email): User
    {
        try {
            $user = $this->findUserByEmailQueryHandler->find($email);
        } catch (DuplicateEmailException) {
            throw new UnauthorizedHttpException('Bearer', 'Authentication required.');
        }

        if (!$user instanceof User) {
            throw new UnauthorizedHttpException('Bearer', 'Authentication required.');
        }

        return $user;
    }
}
