<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Application\Bus\Guard\CommandResponseTypeGuard;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\RegisterUserCommandResponse;
use App\User\Application\DTO\UserRegisterDto;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Domain\Entity\UserInterface;

/**
 * @implements ProcessorInterface<UserRegisterDto, UserInterface>
 */
final readonly class RegisterUserProcessor implements ProcessorInterface
{
    public function __construct(
        private SignUpCommandFactoryInterface $commandFactory,
        private CommandBusInterface $commandBus,
        private CommandResponseTypeGuard $commandResponseTypeGuard
    ) {
    }

    /**
     * @param UserRegisterDto $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    #[\Override]
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): UserInterface {
        $command = $this->commandFactory->create(
            $data->email,
            $data->initials,
            $data->password
        );
        $response = $this->commandResponseTypeGuard->expect(
            $this->commandBus->dispatch($command),
            RegisterUserCommandResponse::class
        );

        return $response->user;
    }
}
