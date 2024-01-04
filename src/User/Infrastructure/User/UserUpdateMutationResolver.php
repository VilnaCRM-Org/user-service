<?php

declare(strict_types=1);

namespace App\User\Infrastructure\User;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\Shared\Domain\Bus\Event\EventBus;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Domain\UserRepositoryInterface;
use App\User\Infrastructure\Event\PasswordChangedEvent;
use App\User\Infrastructure\Exception\InvalidPasswordException;
use App\User\Infrastructure\MutationInputValidator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserUpdateMutationResolver implements MutationResolverInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private CommandBus              $commandBus,
        private MutationInputValidator $validator
    ) {
    }

    public function __invoke(?object $item, array $context): ?object
    {
        $this->validator->validate($item);

        $userId = $item->userId;
        $user = $this->userRepository->find($userId);

        $newEmail = $item->email ?? $user->getEmail();
        $newInitials = $item->initials ?? $user->getInitials();
        $newPassword = $item->newPassword ?? $item->oldPassword;

        $this->commandBus->dispatch(
            new UpdateUserCommand($user, $newEmail, $newInitials, $newPassword, $item->oldPassword));

        return $user;
    }
}
