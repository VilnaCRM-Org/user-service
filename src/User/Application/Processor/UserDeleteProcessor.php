<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Factory\DeleteUserCommandFactoryInterface;
use App\User\Domain\Entity\User;

/**
 * @implements ProcessorInterface<User, User>
 */
final readonly class UserDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private DeleteUserCommandFactoryInterface $commandFactory
    ) {
    }

    /**
     * @param User $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    #[\Override]
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): User {
        $this->commandBus->dispatch(
            $this->commandFactory->create($data)
        );

        return $data;
    }
}
