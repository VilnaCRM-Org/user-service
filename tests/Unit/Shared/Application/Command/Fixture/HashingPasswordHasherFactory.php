<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command\Fixture;

use Symfony\Component\PasswordHasher\Hasher\PasswordHasherAwareInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

final class HashingPasswordHasherFactory implements PasswordHasherFactoryInterface
{
    public function getPasswordHasher(
        string|PasswordAuthenticatedUserInterface|PasswordHasherAwareInterface $user
    ): PasswordHasherInterface {
        return new class() implements PasswordHasherInterface {
            public function hash(string $plainPassword): string
            {
                return 'hashed-' . $plainPassword;
            }

            public function verify(string $hashedPassword, string $plainPassword): bool
            {
                return $hashedPassword === 'hashed-' . $plainPassword;
            }

            public function needsRehash(string $hashedPassword): bool
            {
                return false;
            }
        };
    }
}
