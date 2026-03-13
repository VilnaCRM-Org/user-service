<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use App\User\Application\DTO\AuthorizationUserDto;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class CurrentUserIdentityResolver
{
    public function __construct(private Security $security)
    {
    }

    public function resolveEmail(): string
    {
        $user = $this->security->getUser();

        $identifier = $user?->getUserIdentifier() ?? '';
        if ($identifier !== '') {
            return $identifier;
        }

        throw new UnauthorizedHttpException(
            'Bearer',
            'Authentication required.'
        );
    }

    public function resolveSessionId(): string
    {
        $token = $this->security->getToken();
        if ($token === null) {
            return '';
        }

        $sid = $token->getAttribute('sid');

        return is_string($sid) ? $sid : '';
    }

    public function resolveSessionIdOrFail(): string
    {
        $sessionId = $this->resolveSessionId();

        if ($sessionId !== '') {
            return $sessionId;
        }

        throw new UnauthorizedHttpException(
            'Bearer',
            'Session ID not found in token.'
        );
    }

    public function resolveUserId(): string
    {
        $user = $this->security->getUser();

        if ($user instanceof AuthorizationUserDto) {
            return $user->getId()->__toString();
        }

        throw new UnauthorizedHttpException(
            'Bearer',
            'Authentication required.'
        );
    }
}
