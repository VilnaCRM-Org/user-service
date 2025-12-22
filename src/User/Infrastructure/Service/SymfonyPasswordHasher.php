<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Service;

use App\User\Domain\Entity\User;
use App\User\Domain\Service\PasswordHasherInterface;
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
        $hasher = $this->hasherFactory->getPasswordHasher(User::class);

        return $hasher->hash($plainPassword);
    }
}
