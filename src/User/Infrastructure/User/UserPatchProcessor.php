<?php

declare(strict_types=1);

namespace App\User\Infrastructure\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Event\EventBus;
use App\User\Application\DTO\User\UserPutDto;
use App\User\Domain\Entity\User;
use App\User\Domain\UserRepositoryInterface;
use App\User\Infrastructure\Event\PasswordChangedEvent;
use App\User\Infrastructure\Exception\InvalidPasswordException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @implements ProcessorInterface<User>
 */
class UserPatchProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EventBus $eventBus
    ) {
    }

    /**
     * @param UserPutDto $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $userId = $uriVariables['id'];
        $user = $this->userRepository->find((string)$userId);
        $passwordChanged = false;
        if ($this->passwordHasher->isPasswordValid($user, $data->oldPassword)) {
            if ($data->email) {
                $user->setEmail($data->email);
            }
            if ($data->initials) {
                $user->setInitials($data->initials);
            }
            if ($data->newPassword) {
                $passwordChanged = true;
                $hashedPassword = $this->passwordHasher->hashPassword(
                    $user,
                    $data->newPassword
                );
                $user->setPassword($hashedPassword);
            }
            $this->userRepository->save($user);

            if ($passwordChanged) {
                $this->eventBus->publish(new PasswordChangedEvent($user->getEmail()));
            }

            return $user;
        }
        throw new InvalidPasswordException();
    }
}
