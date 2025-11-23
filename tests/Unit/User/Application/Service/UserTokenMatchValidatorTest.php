<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Service;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Service\UserTokenMatchValidator;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\PasswordResetTokenMismatchException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;

final class UserTokenMatchValidatorTest extends UnitTestCase
{
    private UserRepositoryInterface $userRepository;
    private UserTokenMatchValidator $validator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->validator = new UserTokenMatchValidator($this->userRepository);
    }

    public function testValidateAndGetUserSuccess(): void
    {
        $tokenUserId = $this->faker->uuid();
        $requestUserId = $tokenUserId; // Same user

        $token = $this->createMock(PasswordResetTokenInterface::class);
        $token->expects($this->once())
            ->method('getUserID')
            ->willReturn($tokenUserId);

        $user = $this->createMock(UserInterface::class);
        $user->expects($this->once())
            ->method('getId')
            ->willReturn($tokenUserId);

        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($tokenUserId)
            ->willReturn($user);

        $result = $this->validator->validateAndGetUser($token, $requestUserId);

        $this->assertSame($user, $result);
    }

    public function testValidateAndGetUserThrowsUserNotFound(): void
    {
        $tokenUserId = $this->faker->uuid();
        $requestUserId = $this->faker->uuid();

        $token = $this->createMock(PasswordResetTokenInterface::class);
        $token->expects($this->once())
            ->method('getUserID')
            ->willReturn($tokenUserId);

        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($tokenUserId)
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);
        $this->validator->validateAndGetUser($token, $requestUserId);
    }

    public function testValidateAndGetUserThrowsTokenMismatch(): void
    {
        $tokenUserId = $this->faker->uuid();
        $requestUserId = $this->faker->uuid(); // Different user

        $token = $this->createMock(PasswordResetTokenInterface::class);
        $token->expects($this->once())
            ->method('getUserID')
            ->willReturn($tokenUserId);

        $user = $this->createMock(UserInterface::class);
        $user->expects($this->once())
            ->method('getId')
            ->willReturn($tokenUserId);

        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($tokenUserId)
            ->willReturn($user);

        $this->expectException(PasswordResetTokenMismatchException::class);
        $this->validator->validateAndGetUser($token, $requestUserId);
    }
}
