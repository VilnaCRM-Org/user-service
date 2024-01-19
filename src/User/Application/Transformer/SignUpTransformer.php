<?php

declare(strict_types=1);

namespace App\User\Application\Transformer;

use App\Shared\Application\Transformer\UuidTransformer;
use App\User\Application\Command\SignUpCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use Symfony\Component\Uid\Factory\UuidFactory;

final class SignUpTransformer
{
    public function __construct(
        private UserFactory $userFactory,
        private UuidTransformer $transformer,
        private UuidFactory $uuidFactory,
    ) {
    }

    public function transformToUser(SignUpCommand $command): User
    {
        return $this->userFactory->create(
            $command->email,
            $command->initials,
            $command->password,
            $this->transformer->transformFromSymfonyUuid($this->uuidFactory->create())
        );
    }
}
