<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Security;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Security\SymfonyPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface as SymfonyHasherInterface;

final class SymfonyPasswordHasherTest extends UnitTestCase
{
    public function testHashReturnsHashedPassword(): void
    {
        $plainPassword = 'test-password-123';
        $hashedPassword = '$2y$13$hashed...';

        $symfonyHasher = $this->createMock(SymfonyHasherInterface::class);
        $symfonyHasher->expects($this->once())
            ->method('hash')
            ->with($plainPassword)
            ->willReturn($hashedPassword);

        $hasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $hasherFactory->expects($this->once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($symfonyHasher);

        $passwordHasher = new SymfonyPasswordHasher($hasherFactory);

        $result = $passwordHasher->hash($plainPassword);

        $this->assertSame($hashedPassword, $result);
    }

    public function testVerifyDelegatesToSymfonyHasher(): void
    {
        $hashedPassword = '$2y$13$hashed...';
        $plainPassword = 'test-password-123';

        $symfonyHasher = $this->createMock(SymfonyHasherInterface::class);
        $symfonyHasher->expects($this->once())
            ->method('verify')
            ->with($hashedPassword, $plainPassword)
            ->willReturn(true);

        $hasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $hasherFactory->expects($this->once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($symfonyHasher);

        $passwordHasher = new SymfonyPasswordHasher($hasherFactory);

        $this->assertTrue($passwordHasher->verify($hashedPassword, $plainPassword));
    }

    public function testNeedsRehashDelegatesToSymfonyHasher(): void
    {
        $hashedPassword = '$2y$04$old-cost-hash...';

        $symfonyHasher = $this->createMock(SymfonyHasherInterface::class);
        $symfonyHasher->expects($this->once())
            ->method('needsRehash')
            ->with($hashedPassword)
            ->willReturn(true);

        $hasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $hasherFactory->expects($this->once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($symfonyHasher);

        $passwordHasher = new SymfonyPasswordHasher($hasherFactory);

        $this->assertTrue($passwordHasher->needsRehash($hashedPassword));
    }
}
