<?php

namespace App\User\Infrastructure\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\SendConfirmationEmailCommand;
use App\User\Application\SignUpCommand;
use App\User\Domain\Entity\User\UserInputDto;

readonly class RegisterUserProcessor implements ProcessorInterface
{
    public function __construct(private CommandBus $commandBus)
    {
    }

    /**
     * @param UserInputDto $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): object
    {
        $command = new SignUpCommand($data->email, $data->initials, $data->password);
        $this->commandBus->dispatch($command);

        $user = $command->getResponse()->getCreatedUser();

        $this->commandBus->dispatch(new SendConfirmationEmailCommand($user->getEmail(), $user->getId()));

        return $user;
    }
}
