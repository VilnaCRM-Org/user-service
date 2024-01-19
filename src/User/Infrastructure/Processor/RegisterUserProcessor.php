<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\SignUpCommand;
use App\User\Application\DTO\UserRegisterDto;
use App\User\Domain\Entity\User;

/**
 * @implements ProcessorInterface<User>
 */
final readonly class RegisterUserProcessor implements ProcessorInterface
{
    public function __construct(private CommandBusInterface $commandBus)
    {
    }

    /**
     * @param UserRegisterDto $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        $command = new SignUpCommand($data->email, $data->initials, $data->password);
        $this->commandBus->dispatch($command);

        return $command->getResponse()->createdUser;
    }
}
