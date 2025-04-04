<?php

declare(strict_types=1);

namespace App\User\Application\Transformer;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Domain\Entity\UserInterface;

final readonly class UserTransformer
{
    public function __construct(private UuidTransformer $transformer)
    {
    }

    public function transformToAuthorizationUser(
        UserInterface $user
    ): AuthorizationUserDto {
        return new AuthorizationUserDto(
            $user->getEmail(),
            $user->getInitials(),
            $user->getPassword(),
            $this->transformer->transformFromString($user->getId()),
            $user->isConfirmed(),
        );
    }
}
