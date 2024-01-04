<?php

declare(strict_types=1);

namespace App\User\Infrastructure\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\Command\SignUpCommand;
use App\User\Application\DTO\User\UserInputDto;
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

        return $command->getResponse()->getCreatedUser();
    }
}
