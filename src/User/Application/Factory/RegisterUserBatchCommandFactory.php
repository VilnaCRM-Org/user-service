<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\RegisterUserBatchCommand;
use App\User\Application\DTO\BatchUserRegistrationInput;
use App\User\Application\DTO\BatchUserRegistrationInputCollection;
use App\User\Application\DTO\UserRegisterBatchDto;
use InvalidArgumentException;

final readonly class RegisterUserBatchCommandFactory implements
    RegisterUserBatchCommandFactoryInterface
{
    #[\Override]
    public function create(
        UserRegisterBatchDto $batch
    ): RegisterUserBatchCommand {
        return new RegisterUserBatchCommand(
            $this->createInputCollection($batch)
        );
    }

    private function createInputCollection(
        UserRegisterBatchDto $batch
    ): BatchUserRegistrationInputCollection {
        $users = new BatchUserRegistrationInputCollection();

        foreach ($batch->users as $user) {
            if (
                !is_array($user)
                || !isset($user['email'], $user['initials'], $user['password'])
                || !is_string($user['email'])
                || !is_string($user['initials'])
                || !is_string($user['password'])
            ) {
                throw new InvalidArgumentException(
                    'Batch user payload must contain string email, initials, and password fields.'
                );
            }

            $users->add(new BatchUserRegistrationInput(
                $user['email'],
                $user['initials'],
                $user['password']
            ));
        }

        return $users;
    }
}
