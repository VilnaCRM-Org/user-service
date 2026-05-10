<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Application\Factory\AuthPayloadFactory;
use App\User\Application\Resolver\CurrentUserIdentityResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

abstract class AuthMutationResolverTestCase extends UnitTestCase
{
    protected function authPayloadFactory(): AuthPayloadFactory
    {
        return new AuthPayloadFactory();
    }

    protected function currentUserIdentityResolver(
        string $email,
        string $sessionId,
        string $userId,
    ): CurrentUserIdentityResolver {
        return new CurrentUserIdentityResolver(
            $this->security(
                $this->authorizationUser($email, $userId),
                $this->token($sessionId)
            )
        );
    }

    private function security(
        AuthorizationUserDto $user,
        TokenInterface $token,
    ): Security {
        $security = $this->createMock(Security::class);
        $security->method('getUser')
            ->willReturn($user);
        $security->method('getToken')
            ->willReturn($token);

        return $security;
    }

    private function token(string $sessionId): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getAttribute')
            ->with('sid')
            ->willReturn($sessionId);

        return $token;
    }

    private function authorizationUser(
        string $email,
        string $userId,
    ): AuthorizationUserDto {
        return new AuthorizationUserDto(
            $email,
            $this->faker->name(),
            $this->faker->password(),
            new Uuid($userId),
            true
        );
    }
}
