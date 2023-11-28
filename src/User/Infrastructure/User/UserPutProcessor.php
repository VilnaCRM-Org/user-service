<?php

namespace App\User\Infrastructure\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\User\Domain\Entity\User\UserPutDto;
use App\User\Domain\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserPutProcessor implements ProcessorInterface
{
    public function __construct(private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher)
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

            return $user;
        } else {
            throw new InvalidPasswordError();
        }
    }
}
