<?php

declare(strict_types=1);

namespace App\User\Domain\Factory;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;

final readonly class UserFactory implements UserFactoryInterface
{
    /**
     * @return User
     */
    #[\Override]
    public function create(
        string $email,
        string $initials,
        string $password,
        Uuid $id
    ): UserInterface {
        return new User($email, $initials, $password, $id);
    }
}
