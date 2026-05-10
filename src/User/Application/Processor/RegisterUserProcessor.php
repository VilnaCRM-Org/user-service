<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\UserRegisterDto;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Application\Query\FindUserByEmailQueryHandlerInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;

/**
 * @implements ProcessorInterface<UserRegisterDto, User>
 */
final readonly class RegisterUserProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private SignUpCommandFactoryInterface $signUpCommandFactory,
        private FindUserByEmailQueryHandlerInterface $findUserByEmailQueryHandler
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
    ): User {
        $existingUser = $this->findUserByEmailQueryHandler->find($data->email);
        if ($existingUser !== null) {
            return $existingUser;
        }

        $command = $this->signUpCommandFactory->create(
            $data->email,
            $data->initials,
            $data->password
        );
        $this->commandBus->dispatch($command);

        return $this->findUserByEmailQueryHandler->find($data->email)
            ?? throw new UserNotFoundException();
    }
}
