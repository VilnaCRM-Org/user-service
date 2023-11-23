<?php

namespace App\User\Infrastructure\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\SignUpCommand;
use App\User\Domain\Entity\User\User;
use App\User\Domain\Entity\User\UserInputDto;

readonly class RegisterUserProcessor implements ProcessorInterface
{
    public function __construct(private CommandBus $commandBus)
    {
    }

    /**
     * @param UserInputDto $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        $id = Uuid::random()->value();
        $plaintextPassword = $data->password;
        $user = new User($id, $data->email, $data->initials, $plaintextPassword);

        $commandResponse = $this->commandBus->dispatch(
            new SignUpCommand($data->email, $data->initials, $plaintextPassword));

        return $user;
    }
}
