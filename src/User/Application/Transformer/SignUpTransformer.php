<?php

declare(strict_types=1);

namespace App\User\Application\Transformer;

use App\Shared\Application\Transformer\UuidTransformer;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class SignUpTransformer
{
    public function __construct(
        private UserFactoryInterface $userFactory,
        private UuidTransformer $transformer,
        private UuidFactory $uuidFactory,
    ) {
    }

    public function transformToUser(RegisterUserCommand $command): User
    {
        return $this->userFactory->create(
            $command->email,
            $command->initials,
            $command->password,
            $this->transformer->transformFromSymfonyUuid(
                $this->uuidFactory->create()
            )
        );
    }
}
