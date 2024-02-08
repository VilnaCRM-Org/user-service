<?php

namespace App\Tests\Behat\UserContext;

use App\Shared\Application\Transformer\UuidTransformer;
use App\User\Application\Exception\DuplicateEmailException;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Behat\Behat\Context\Context;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

class UserContext implements Context
{
    private Generator $faker;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherFactoryInterface $hasherFactory,
        private TokenRepositoryInterface $tokenRepository,
        private UserFactoryInterface $userFactory,
        private UuidTransformer $transformer,
        private UuidFactory $uuidFactory,
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
            $user = $this->userFactory->create(
                $email,
                $this->faker->name,
                $password,
                $this->transformer->transformFromSymfonyUuid($this->uuidFactory->create())
            );

            $hasher = $this->hasherFactory->getPasswordHasher(get_class($user));
            $hashedPassword = $hasher->hash($password, null);
            $user->setPassword($hashedPassword);

            $this->userRepository->save($user);
        } catch (DuplicateEmailException) {
        }
    }

    /**
     * @Given user with email :email exists
     */
    public function userWithEmailExists(string $email): void
    {
        try {
            $password = $this->faker->password;
            $user = $this->userFactory->create(
                $email,
                $this->faker->name,
                $password,
                $this->transformer->transformFromSymfonyUuid($this->uuidFactory->create())
            );

            $hasher = $this->hasherFactory->getPasswordHasher(get_class($user));
            $hashedPassword = $hasher->hash($password, null);
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
                $this->transformer->transformFromString($id)
            );
        $this->userRepository->save($user);
    }

    /**
     * @Given user with id :id and password :password exists
     */
    public function userWithIdAndPasswordExists(string $id, string $password): void
    {
        $user = $this->userRepository->find($id) ?? $this->userFactory->
        create(
            $this->faker->email,
            $this->faker->name,
            $password,
            $this->transformer->transformFromString($id)
        );

        $hasher = $this->hasherFactory->getPasswordHasher(get_class($user));
        $hashedPassword = $hasher->hash($password, null);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);
    }
}
