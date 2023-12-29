<?php

namespace App\User\Infrastructure\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\Command\SendConfirmationEmailCommand;
use App\User\Application\Command\SignUpCommand;
use App\User\Application\DTO\User\UserInputDto;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Entity\User;

/**
 * @implements ProcessorInterface<User>
 */
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
        $token = ConfirmationToken::generateToken($user->getId());

        $this->commandBus->dispatch(new SendConfirmationEmailCommand($user->getEmail(), $token));

        return $user;
    }
}
