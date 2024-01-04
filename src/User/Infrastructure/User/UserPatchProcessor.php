<?php

declare(strict_types=1);

namespace App\User\Infrastructure\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Application\DTO\User\UserPutDto;
use App\User\Domain\Entity\User;
use App\User\Domain\UserRepositoryInterface;

/**
 * @implements ProcessorInterface<User>
 */
class UserPatchProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private CommandBus $commandBus
    ) {
    }

    /**
     * @param UserPutDto $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $userId = $uriVariables['id'];
        $user = $this->userRepository->find((string) $userId);

        $newEmail = $data->email ?? $user->getEmail();
        $newInitials = $data->initials ?? $user->getInitials();
        $newPassword = $data->newPassword ?? $data->oldPassword;

        $this->commandBus->dispatch(
            new UpdateUserCommand($user, $newEmail, $newInitials, $newPassword, $data->oldPassword));

        return $user;
    }
}
