<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Application\Resolver\CurrentUserIdentityResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class CurrentUserIdentityResolverTest extends UnitTestCase
{
    public function testResolveEmailReturnsAuthenticatedIdentifier(): void
    {
        $email = $this->faker->email();
        $resolver = new CurrentUserIdentityResolver(
            $this->createSecurityWithUser($this->createSecurityUser($email))
        );

        $this->assertSame($email, $resolver->resolveEmail());
    }

    public function testResolveEmailFailsWhenIdentifierIsEmpty(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')
            ->willReturn('');
        $resolver = new CurrentUserIdentityResolver(
            $this->createSecurityWithUser($user)
        );

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Authentication required.');

        $resolver->resolveEmail();
    }

    public function testResolveSessionIdReturnsEmptyStringWithoutToken(): void
    {
        $security = $this->createMock(Security::class);
        $security->method('getToken')
            ->willReturn(null);
        $resolver = new CurrentUserIdentityResolver($security);

        $this->assertSame('', $resolver->resolveSessionId());
    }

    public function testResolveSessionIdReturnsTokenAttributeWhenItIsAString(): void
    {
        $sessionId = $this->faker->uuid();
        $resolver = new CurrentUserIdentityResolver(
            $this->createSecurityWithSessionId($sessionId)
        );

        $this->assertSame($sessionId, $resolver->resolveSessionId());
    }

    public function testResolveSessionIdReturnsEmptyStringForNonStringTokenAttribute(): void
    {
        $security = $this->createMock(Security::class);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getAttribute')
            ->with('sid')
            ->willReturn(123);
        $security->method('getToken')
            ->willReturn($token);
        $resolver = new CurrentUserIdentityResolver($security);

        $this->assertSame('', $resolver->resolveSessionId());
    }

    public function testResolveSessionIdOrFailReturnsResolvedSessionId(): void
    {
        $sessionId = $this->faker->uuid();
        $resolver = new CurrentUserIdentityResolver(
            $this->createSecurityWithSessionId($sessionId)
        );

        $this->assertSame($sessionId, $resolver->resolveSessionIdOrFail());
    }

    public function testResolveSessionIdOrFailFailsWhenSessionIdIsMissing(): void
    {
        $security = $this->createMock(Security::class);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getAttribute')
            ->with('sid')
            ->willReturn('');
        $security->method('getToken')
            ->willReturn($token);
        $resolver = new CurrentUserIdentityResolver($security);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Session ID not found in token.');

        $resolver->resolveSessionIdOrFail();
    }

    public function testResolveUserIdReturnsAuthenticatedAuthorizationUserId(): void
    {
        $userId = $this->faker->uuid();
        $resolver = new CurrentUserIdentityResolver(
            $this->createSecurityWithUser($this->createAuthorizationUser($userId))
        );

        $this->assertSame($userId, $resolver->resolveUserId());
    }

    public function testResolveUserIdFailsForUnsupportedUserType(): void
    {
        $resolver = new CurrentUserIdentityResolver(
            $this->createSecurityWithUser($this->createSecurityUser($this->faker->email()))
        );

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Authentication required.');

        $resolver->resolveUserId();
    }

    private function createSecurityWithUser(UserInterface $user): Security
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')
            ->willReturn($user);

        return $security;
    }

    private function createSecurityWithSessionId(string $sessionId): Security
    {
        $security = $this->createMock(Security::class);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getAttribute')
            ->with('sid')
            ->willReturn($sessionId);
        $security->method('getToken')
            ->willReturn($token);

        return $security;
    }

    private function createSecurityUser(string $email): UserInterface
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')
            ->willReturn($email);

        return $user;
    }

    private function createAuthorizationUser(string $userId): AuthorizationUserDto
    {
        return new AuthorizationUserDto(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            new Uuid($userId),
            true
        );
    }
}
