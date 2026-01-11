<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Service;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Service\SymfonyPasswordHasher;
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
}
