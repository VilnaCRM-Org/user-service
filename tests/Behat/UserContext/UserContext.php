<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final class UserContext implements Context
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
     * @BeforeScenario
     */
    public function truncateUsersTable(BeforeScenarioScope $scope): void
    {
        $em = method_exists($this->userRepository, 'getEntityManager') ? $this->userRepository->getEntityManager() : (method_exists($this->userRepository, 'getManager') ? $this->userRepository->getManager() : null);
        if ($em) {
            $connection = $em->getConnection();
            $connection->executeStatement('DELETE FROM user'); // use DELETE instead of TRUNCATE
        }
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
        $userRepo = $this->userRepository;
        $existingUser = $userRepo->findOneBy(['email' => $email]);
        if ($existingUser) {
            $em = method_exists($userRepo, 'getEntityManager') ? $userRepo->getEntityManager() : (method_exists($userRepo, 'getManager') ? $userRepo->getManager() : null);
            if ($em) {
                $em->remove($existingUser);
                $em->flush();
            }
        }
        $user = $this->userFactory->create(
            $email,
            $this->faker->name,
            $password,
            $this->transformer->transformFromSymfonyUuid(
                $this->uuidFactory->create()
            )
        );

        $hasher = $this->hasherFactory->getPasswordHasher($user::class);
        $hashedPassword = $hasher->hash($password, null);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);
    }

    /**
     * @Given user with email :email exists
     */
    public function userWithEmailExists(string $email): void
    {
        $userRepo = $this->userRepository;
        $existingUser = $userRepo->findOneBy(['email' => $email]);
        if ($existingUser) {
            $em = method_exists($userRepo, 'getEntityManager') ? $userRepo->getEntityManager() : (method_exists($userRepo, 'getManager') ? $userRepo->getManager() : null);
            if ($em) {
                $em->remove($existingUser);
                $em->flush();
            }
        }
        $password = $this->faker->password;
        $user = $this->userFactory->create(
            $email,
            $this->faker->name,
            $password,
            $this->transformer->transformFromSymfonyUuid(
                $this->uuidFactory->create()
            )
        );

        $hasher = $this->hasherFactory->getPasswordHasher($user::class);
        $hashedPassword = $hasher->hash($password, null);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);
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
    public function userWithIdAndPasswordExists(
        string $id,
        string $password
    ): void {
        $user = $this->userRepository->find($id) ?? $this->userFactory->create(
            $this->faker->email,
            $this->faker->name,
            $password,
            $this->transformer->transformFromString($id)
        );

        $hasher = $this->hasherFactory->getPasswordHasher($user::class);
        $hashedPassword = $hasher->hash($password, null);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);
    }
}
