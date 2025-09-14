<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Service;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Service\UserPasswordService;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class UserPasswordServiceTest extends UnitTestCase
{
    private UserPasswordService $userPasswordService;
    private MockObject|PasswordHasherFactoryInterface $hasherFactory;
    private MockObject|UserRepositoryInterface $userRepository;
    private MockObject|PasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);

        $this->userPasswordService = new UserPasswordService(
            $this->hasherFactory,
            $this->userRepository
        );
    }

    public function testUpdateUserPassword(): void
    {
        $user = $this->createMock(User::class);
        $newPassword = $this->faker->password(12);
        $hashedPassword = $this->faker->sha256();

        $this->hasherFactory
            ->expects($this->once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($this->passwordHasher);

        $this->passwordHasher
            ->expects($this->once())
            ->method('hash')
            ->with($newPassword)
            ->willReturn($hashedPassword);

        $user
            ->expects($this->once())
            ->method('setPassword')
            ->with($hashedPassword);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $this->userPasswordService->updateUserPassword($user, $newPassword);
    }

    public function testHashPassword(): void
    {
        $password = $this->faker->password(12);
        $expectedHash = $this->faker->sha256();

        $this->hasherFactory
            ->expects($this->once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($this->passwordHasher);

        $this->passwordHasher
            ->expects($this->once())
            ->method('hash')
            ->with($password)
            ->willReturn($expectedHash);

        $result = $this->userPasswordService->hashPassword($password);

        $this->assertSame($expectedHash, $result);
    }
}
