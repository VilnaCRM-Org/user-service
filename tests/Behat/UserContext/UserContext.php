<?php

namespace App\Tests\Behat\UserContext;

use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Entity\UserFactory;
use App\User\Domain\TokenRepositoryInterface;
use App\User\Domain\UserRepositoryInterface;
use App\User\Infrastructure\Exception\DuplicateEmailException;
use App\User\Infrastructure\Exception\UserNotFoundException;
use Behat\Behat\Context\Context;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

class UserContext implements Context
{
    private Generator $faker;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private TokenRepositoryInterface $tokenRepository,
        private UserFactory $userFactory
    ) {
        $this->faker = Factory::create();
    }

    /**
     * @Given user with id :id has confirmation token :token
     */
    public function userHasConfirmationToken(string $id, string $token): void
    {
        $token = new ConfirmationToken($token, $id);
        $this->tokenRepository->save($token);
    }

    /**
     * @Given user with email :email and password :password exists
     */
    public function userWithEmailAndPasswordExists(string $email, string $password): void
    {
        try {
            $user = $this->userFactory->create($email, $this->faker->name, $password);

            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $password
            );
            $user->setPassword($hashedPassword);

            $this->userRepository->save($user);
        } catch (DuplicateEmailException) {
        }
    }

    /**
     * @Given user with id :id exists
     */
    public function userWithIdExists(string $id): void
    {
        try {
            $this->userRepository->find($id);
        } catch (UserNotFoundException) {
            $user = $this->userFactory->create($this->faker->email, $this->faker->name, $this->faker->password, Uuid::fromString($id));
            $this->userRepository->save($user);
        }
    }

    /**
     * @Given user with id :id and password :password exists
     */
    public function userWithIdAndPasswordExists(string $id, string $password): void
    {
        try {
            $user = $this->userRepository->find($id);
            if (!$this->passwordHasher->isPasswordValid($user, $password)) {
                throw new UserNotFoundException();
            }
        } catch (UserNotFoundException) {
            $user = $this->userFactory->
            create($this->faker->email, $this->faker->name, $password, Uuid::fromString($id));

            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $password
            );
            $user->setPassword($hashedPassword);

            $this->userRepository->save($user);
        }
    }
}
