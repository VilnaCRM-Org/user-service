<?php

namespace App\User\Infrastructure;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInputDto;
use App\User\Domain\UserRepository;

readonly class RegisterUserProcessor implements ProcessorInterface
{
    public function __construct(private UserRepository $repository)
    {
    }

    /**
     * @param UserInputDto $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        $id = Uuid::random()->value();
        $user = new User($id, $data->email, $data->initials, $data->password);
        $this->repository->save($user);

        return $user;
    }
}
