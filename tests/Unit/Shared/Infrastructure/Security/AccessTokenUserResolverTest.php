<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Security;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Security\AccessTokenUserResolver;
use App\Shared\Infrastructure\Security\ServicePrincipal;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Application\Transformer\UserTransformer;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

final class AccessTokenUserResolverTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private AuthSessionRepositoryInterface&MockObject $authSessionRepository;
    private AccessTokenUserResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->authSessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);

        $userTransformer = new UserTransformer(new UuidTransformer(new UuidFactory()));

        $this->resolver = new AccessTokenUserResolver(
            $this->userRepository,
            $userTransformer,
            $this->authSessionRepository,
        );
    }

    public function testResolveReturnsServicePrincipalForServiceRole(): void
    {
        $subject = $this->faker->word();
        $roles = ['ROLE_SERVICE'];
        $sid = $this->faker->uuid();

        $this->authSessionRepository->expects($this->never())->method('findById');

        $result = $this->resolver->resolve($subject, $roles, $sid);

        $this->assertInstanceOf(ServicePrincipal::class, $result);
        $this->assertSame($subject, $result->getUserIdentifier());
        $this->assertSame($roles, $result->getRoles());
    }

    public function testResolveReturnsByEmailWhenUserFound(): void
    {
        $email = $this->faker->email();
        $sid = $this->faker->uuid();
        $user = $this->createDomainUser($email);
        $validSession = $this->createValidSession($sid);

        $this->authSessionRepository->method('findById')->with($sid)->willReturn($validSession);
        $this->userRepository->method('findByEmail')->with($email)->willReturn($user);

        $result = $this->resolver->resolve($email, [], $sid);

        $this->assertInstanceOf(AuthorizationUserDto::class, $result);
        $this->assertSame($email, $result->getUserIdentifier());
    }

    public function testResolveFindsUserByIdWhenSubjectLooksLikeUuid(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $sid = $this->faker->uuid();
        $user = $this->createDomainUser($this->faker->email());
        $validSession = $this->createValidSession($sid);

        $this->authSessionRepository->method('findById')->willReturn($validSession);
        $this->userRepository->method('findByEmail')->with($uuid)->willReturn(null);
        $this->userRepository->expects($this->once())->method('findById')->with($uuid)->willReturn($user);

        $result = $this->resolver->resolve($uuid, [], $sid);

        $this->assertInstanceOf(AuthorizationUserDto::class, $result);
    }

    public function testResolveThrowsWhenUserNotFoundByEmailOrNonUuidSubject(): void
    {
        $email = $this->faker->email();
        $sid = $this->faker->uuid();
        $validSession = $this->createValidSession($sid);

        $this->authSessionRepository->method('findById')->willReturn($validSession);
        $this->userRepository->method('findByEmail')->willReturn(null);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Authentication required.');

        $this->resolver->resolve($email, [], $sid);
    }

    public function testResolveThrowsWhenUserNotFoundByUuid(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $sid = $this->faker->uuid();
        $validSession = $this->createValidSession($sid);

        $this->authSessionRepository->method('findById')->willReturn($validSession);
        $this->userRepository->method('findByEmail')->willReturn(null);
        $this->userRepository->method('findById')->willReturn(null);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Authentication required.');

        $this->resolver->resolve($uuid, [], $sid);
    }

    public function testResolveThrowsWhenSessionNotFound(): void
    {
        $this->authSessionRepository->method('findById')->willReturn(null);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token claims.');

        $this->resolver->resolve($this->faker->email(), [], $this->faker->uuid());
    }

    public function testResolveThrowsWhenSessionExpired(): void
    {
        $session = new AuthSession(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            new DateTimeImmutable('-2 days'),
            new DateTimeImmutable('-1 day'),
            false
        );

        $this->authSessionRepository->method('findById')->willReturn($session);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token claims.');

        $this->resolver->resolve($this->faker->email(), [], $this->faker->uuid());
    }

    public function testResolveThrowsWhenSessionRevoked(): void
    {
        $session = $this->createValidSession($this->faker->uuid());
        $session->revoke();

        $this->authSessionRepository->method('findById')->willReturn($session);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token claims.');

        $this->resolver->resolve($this->faker->email(), [], $this->faker->uuid());
    }

    public function testResolveDoesNotTreatPrefixedUuidAsUuid(): void
    {
        $subject = 'prefix-' . '550e8400-e29b-41d4-a716-446655440000';
        $sid = $this->faker->uuid();
        $validSession = $this->createValidSession($sid);

        $this->authSessionRepository->method('findById')->willReturn($validSession);
        $this->userRepository->method('findByEmail')->willReturn(null);
        $this->userRepository->expects($this->never())->method('findById');

        $this->expectException(CustomUserMessageAuthenticationException::class);

        $this->resolver->resolve($subject, [], $sid);
    }

    public function testResolveDoesNotTreatSuffixedUuidAsUuid(): void
    {
        $subject = '550e8400-e29b-41d4-a716-446655440000' . '-suffix';
        $sid = $this->faker->uuid();
        $validSession = $this->createValidSession($sid);

        $this->authSessionRepository->method('findById')->willReturn($validSession);
        $this->userRepository->method('findByEmail')->willReturn(null);
        $this->userRepository->expects($this->never())->method('findById');

        $this->expectException(CustomUserMessageAuthenticationException::class);

        $this->resolver->resolve($subject, [], $sid);
    }

    private function createDomainUser(string $email): User
    {
        $transformer = new UuidTransformer(new UuidFactory());

        return new User(
            $email,
            $this->faker->name(),
            $this->faker->sha256(),
            $transformer->transformFromString($this->faker->uuid())
        );
    }

    private function createValidSession(string $sid): AuthSession
    {
        return new AuthSession(
            $sid,
            $this->faker->uuid(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            new DateTimeImmutable(),
            new DateTimeImmutable('+1 day'),
            false
        );
    }
}
