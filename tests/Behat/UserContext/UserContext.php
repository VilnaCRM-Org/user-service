<?php

namespace App\Tests\Behat\UserContext;

use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UuidFactory;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Exception\DuplicateEmailException;
use Behat\Behat\Context\Context;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserContext implements Context
{
    private Generator $faker;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private TokenRepositoryInterface $tokenRepository,
        private UserFactory $userFactory,
        private UuidFactory $uuidFactory
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
        $user = $this->userRepository->find($id) ??
            $this->userFactory->create(
                $this->faker->email,
                $this->faker->name,
                $this->faker->password,
                $this->uuidFactory->createFromString($id)
            );
        $this->userRepository->save($user);
    }

    /**
     * @Given user with id :id and password :password exists
     */
    public function userWithIdAndPasswordExists(string $id, string $password): void
    {
        $user = $this->userRepository->find($id) ?? $this->userFactory->
        create($this->faker->email, $this->faker->name, $password, $this->uuidFactory->createFromString($id));

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $password
        );
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);
    }
}
