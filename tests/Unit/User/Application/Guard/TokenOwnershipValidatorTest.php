<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Guard;

use App\Shared\Domain\ValueObject\UuidInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Application\Validator\TokenOwnershipValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class TokenOwnershipValidatorTest extends UnitTestCase
{
    private TokenStorageInterface&MockObject $tokenStorage;
    private TokenOwnershipValidator $guard;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->guard = new TokenOwnershipValidator($this->tokenStorage);
    }

    public function testAssertOwnershipSucceedsWhenUserIdMatches(): void
    {
        $userId = $this->faker->uuid();

        $uuid = $this->createMock(UuidInterface::class);
        $uuid->method('__toString')->willReturn($userId);

        $authUser = $this->createMock(AuthorizationUserDto::class);
        $authUser->method('getId')->willReturn($uuid);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($authUser);

        $this->tokenStorage->method('getToken')->willReturn($token);

        $this->guard->assertOwnership($userId);
        $this->addToAssertionCount(1);
    }

    public function testAssertOwnershipThrowsWhenUserIdDoesNotMatch(): void
    {
        $resourceUserId = $this->faker->uuid();
        $authenticatedUserId = $this->faker->uuid();

        $uuid = $this->createMock(UuidInterface::class);
        $uuid->method('__toString')->willReturn($authenticatedUserId);

        $authUser = $this->createMock(AuthorizationUserDto::class);
        $authUser->method('getId')->willReturn($uuid);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($authUser);

        $this->tokenStorage->method('getToken')->willReturn($token);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied.');

        $this->guard->assertOwnership($resourceUserId);
    }

    public function testAssertOwnershipThrowsWhenNoToken(): void
    {
        $this->tokenStorage->method('getToken')->willReturn(null);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied.');

        $this->guard->assertOwnership($this->faker->uuid());
    }

    public function testAssertOwnershipThrowsWhenUserIsNotAuthorizationUserDto(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $this->tokenStorage->method('getToken')->willReturn($token);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied.');

        $this->guard->assertOwnership($this->faker->uuid());
    }
}
