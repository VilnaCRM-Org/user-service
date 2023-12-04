<?php

namespace App\User\Infrastructure\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Event\EventBus;
use App\User\Domain\Entity\User\UserPutDto;
use App\User\Domain\UserRepository;
use App\User\Infrastructure\Event\PasswordChangedEvent;
use App\User\Infrastructure\Exceptions\InvalidPasswordError;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserPutProcessor implements ProcessorInterface
{
    public function __construct(private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher, private EventBus $eventBus)
    {
    }

    /**
     * @param UserPutDto $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $userId = $uriVariables['id'];
        $user = $this->userRepository->find($userId);
        if ($this->passwordHasher->isPasswordValid($user, $data->oldPassword)) {
            $user->setEmail($data->email);
            $user->setInitials($data->initials);
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $data->newPassword
            );
            $user->setPassword($hashedPassword);
            $this->userRepository->save($user);

            $this->eventBus->publish(new PasswordChangedEvent($user->getId(), $user->getEmail()));

            return $user;
        } else {
            throw new InvalidPasswordError();
        }
    }
}
