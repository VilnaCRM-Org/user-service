<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\UserPutDto;
use App\User\Application\Exception\UserNotFoundException;
use App\User\Application\Factory\UpdateUserCommandFactoryInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserUpdateData;

/**
 * @implements ProcessorInterface<UserPutDto, User>
 */
final class UserPutProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private CommandBusInterface $commandBus,
        private UpdateUserCommandFactoryInterface $updateUserCommandFactory
    ) {
    }

    /**
     * @param UserPutDto $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): User {
        $userId = $uriVariables['id'];
        $user = $this->userRepository->find(
            (string) $userId
        ) ?? throw new UserNotFoundException();

        $this->commandBus->dispatch(
            $this->updateUserCommandFactory->create(
                $user,
                new UserUpdateData(
                    $data->email,
                    $data->initials,
                    $data->newPassword,
                    $data->oldPassword
                )
            )
        );

        return $user;
    }
}
