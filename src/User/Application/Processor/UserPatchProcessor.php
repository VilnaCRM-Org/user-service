<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\UserPatchDto;
use App\User\Application\DTO\UserPutDto;
use App\User\Application\Factory\UpdateUserCommandFactoryInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserUpdate;

/**
 * @implements ProcessorInterface<UserPutDto, User>
 */
final readonly class UserPatchProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private CommandBusInterface $commandBus,
        private UpdateUserCommandFactoryInterface $updateUserCommandFactory
    ) {
    }

    /**
     * @param UserPatchDto $data
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
        $user = $this->userRepository->find($userId)
            ?? throw new UserNotFoundException();

        $newEmail = $this->getNewValue($data->email, $user->getEmail());
        $newInitials = $this->getNewValue(
            $data->initials,
            $user->getInitials()
        );
        $newPassword = $this->getNewValue(
            $data->newPassword,
            $data->oldPassword
        );

        $this->dispatchCommand(
            $user,
            $newEmail,
            $newInitials,
            $newPassword,
            $data->oldPassword
        );

        return $user;
    }

    private function getNewValue(string $newValue, string $defaultValue): string
    {
        return strlen(trim($newValue)) > 0 ? $newValue : $defaultValue;
    }

    private function dispatchCommand(
        UserInterface $user,
        string $newEmail,
        string $newInitials,
        string $newPassword,
        string $oldPassword
    ): void {
        $this->commandBus->dispatch(
            $this->updateUserCommandFactory->create(
                $user,
                new UserUpdate(
                    $newEmail,
                    $newInitials,
                    $newPassword,
                    $oldPassword
                )
            )
        );
    }
}
