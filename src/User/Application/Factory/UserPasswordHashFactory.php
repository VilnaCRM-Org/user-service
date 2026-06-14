<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class UserPasswordHashFactory
{
    private const USER_CLASS = 'App\\User\\Domain\\Entity\\User';

    private ?PasswordHasherInterface $hasher = null;

    public function __construct(
        private readonly PasswordHasherFactoryInterface $hasherFactory
    ) {
    }

    public function create(string $plainPassword): string
    {
        return $this->hasher()->hash($plainPassword);
    }

    private function hasher(): PasswordHasherInterface
    {
        $this->hasher ??= $this->hasherFactory->getPasswordHasher(
            self::USER_CLASS
        );

        return $this->hasher;
    }
}
