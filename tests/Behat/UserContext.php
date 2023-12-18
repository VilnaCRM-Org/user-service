<?php

namespace App\Tests\Behat;

use App\User\Domain\Entity\Token\ConfirmationToken;
use App\User\Domain\Entity\User\User;
use App\User\Domain\TokenRepository;
use App\User\Domain\UserRepository;
use App\User\Infrastructure\Exceptions\UserNotFoundError;
use Behat\Behat\Context\Context;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserContext implements Context
{
    private Generator $faker;

    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher, private TokenRepository $tokenRepository
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
     * @Given user with id :id exists
     */
    public function userWithIdExists(string $id): void
    {
        try {
            $this->userRepository->find($id);
        } catch (UserNotFoundError) {
            $this->userRepository->save(new User($id, $this->faker->email, $this->faker->name, $this->faker->password));
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
                throw new UserNotFoundError();
            }
        } catch (UserNotFoundError) {
            $user = new User($id, $this->faker->email, $this->faker->name, $password);

            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                $password
            );
            $user->setPassword($hashedPassword);

            $this->userRepository->save($user);
        }
    }
}
