<?php

namespace App\User\Infrastructure\User;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Event\EventBus;
use App\User\Domain\UserRepositoryInterface;
use App\User\Infrastructure\Event\PasswordChangedEvent;
use App\User\Infrastructure\Exception\InvalidPasswordException;
use App\User\Infrastructure\MutationInputValidator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserUpdateMutationResolver implements MutationResolverInterface
{
    public function __construct(private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface                     $passwordHasher, private EventBus $eventBus,
        private MutationInputValidator                          $validator
    ) {
    }

    public function __invoke(?object $item, array $context): ?object
    {
        $this->validator->validate($item);

        $userId = $item->userId;
        $user = $this->userRepository->find($userId);
        $passwordChanged = false;
        if ($this->passwordHasher->isPasswordValid($user, $item->oldPassword)) {
            if ($item->email) {
                $user->setEmail($item->email);
            }
            if ($item->initials) {
                $user->setInitials($item->initials);
            }
            if ($item->newPassword) {
                $passwordChanged = true;
                $hashedPassword = $this->passwordHasher->hashPassword(
                    $user,
                    $item->newPassword
                );
                $user->setPassword($hashedPassword);
            }
            $this->userRepository->save($user);

            if ($passwordChanged) {
                $this->eventBus->publish(new PasswordChangedEvent($user->getId(), $user->getEmail()));
            }

            return $user;
        } else {
            throw new InvalidPasswordException();
        }
    }
}
