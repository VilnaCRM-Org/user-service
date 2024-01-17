<?php

declare(strict_types=1);

namespace App\User\Application\Transformer;

use App\Shared\Application\Transformer\UuidTransformer;
use App\User\Application\DTO\AuthorizationUser;
use App\User\Domain\Entity\User;

class UserTransformer
{
    public function __construct(private UuidTransformer $transformer)
    {
    }

    public function transformToAuthorizationUser(User $user): AuthorizationUser
    {
        return new AuthorizationUser(
            $user->getEmail(),
            $user->getInitials(),
            $user->getPassword(),
            $this->transformer->transformFromString($user->getId()),
            $user->isConfirmed(),
            $user->getRoles(),
        );
    }
}
