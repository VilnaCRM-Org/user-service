<?php

namespace App\User\Infrastructure\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\User\Domain\Entity\User\UserPutDto;
use App\User\Domain\UserRepository;
use App\User\Infrastructure\Exceptions\InvalidPasswordError;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserPatchProcessor implements ProcessorInterface
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
            if ($data->email) {
                $user->setEmail($data->email);
            }
            if ($data->initials) {
                $user->setInitials($data->initials);
            }
            if ($data->newPassword) {
                $hashedPassword = $this->passwordHasher->hashPassword(
                    $user,
                    $data->newPassword
                );
                $user->setPassword($hashedPassword);
            }
            $this->userRepository->save($user);

            return $user;
        } else {
            throw new InvalidPasswordError();
        }
    }
}
