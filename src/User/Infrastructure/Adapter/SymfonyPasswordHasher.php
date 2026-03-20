<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Adapter;

use App\User\Application\Adapter\PasswordHasherInterface;
use App\User\Domain\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final readonly class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private PasswordHasherFactoryInterface $hasherFactory
    ) {
    }

    #[\Override]
    public function hash(string $plainPassword): string
    {
        return $this->hasherFactory->getPasswordHasher(User::class)->hash($plainPassword);
    }

    #[\Override]
    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        return $this->hasherFactory
            ->getPasswordHasher(User::class)
            ->verify($hashedPassword, $plainPassword);
    }

    #[\Override]
    public function needsRehash(string $hashedPassword): bool
    {
        return $this->hasherFactory->getPasswordHasher(User::class)->needsRehash($hashedPassword);
    }
}
