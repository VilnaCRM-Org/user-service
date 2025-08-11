<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Behat\Behat\Context\Context;
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
    public function truncateUsersTable(): void
    {
        $entityManager = $this->getEntityManager();
        if ($entityManager) {
            $entityManager->createQuery(
                'DELETE FROM App\User\Domain\Entity\User'
            )->execute();
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
    public function userWithEmailAndPasswordExists(
        string $email,
        string $password
    ): void {
        $this->removeExistingUser($email);
        $user = $this->userFactory->create(
            $email,
            'Test User',
            $password,
            $this->transformer->transformFromSymfonyUuid(
                $this->uuidFactory->create()
            )
        );

        $hasher = $this->hasherFactory->getPasswordHasher($user::class);
        $hashedPassword = $hasher->hash($password, null);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);
        $this->flushAndClear();
    }

    /**
     * @Given user with email :email exists
     */
    public function userWithEmailExists(string $email): void
    {
        $this->removeExistingUser($email);
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
        $this->flushAndClear();
    }

    /**
     * @Given user with id :id exists
     */
    public function userWithIdExists(string $id): void
    {
        $email = $this->faker->email;
        $this->removeExistingUser($email);
        $password = $this->faker->password;
        $user = $this->userFactory->create(
            $email,
            $this->faker->name,
            $password,
            $this->transformer->transformFromString($id)
        );

        $hasher = $this->hasherFactory->getPasswordHasher($user::class);
        $hashedPassword = $hasher->hash($password, null);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);
        $this->flushAndClear();
    }

    /**
     * @Given user with id :id and password :password exists
     */
    public function userWithIdAndPasswordExists(
        string $id,
        string $password
    ): void {
        $email = $this->faker->email;
        $this->removeExistingUser($email);
        $user = $this->userFactory->create(
            $email,
            $this->faker->name,
            $password,
            $this->transformer->transformFromString($id)
        );

        $hasher = $this->hasherFactory->getPasswordHasher($user::class);
        $hashedPassword = $hasher->hash($password, null);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);
        $this->flushAndClear();
    }

    private function removeExistingUser(string $email): void
    {
        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser) {
            $this->userRepository->delete($existingUser);
        }
    }

    private function getEntityManager(): ?\Doctrine\ORM\EntityManagerInterface
    {
        if (
            $this->userRepository instanceof
            \Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository
        ) {
            return $this->userRepository->getEntityManager();
        }
        if (method_exists($this->userRepository, 'getEntityManager')) {
            return $this->userRepository->getEntityManager();
        }
        return null;
    }

    private function flushAndClear(): void
    {
        $em = $this->getEntityManager();
        if ($em) {
            $em->flush();
            $em->clear();
        }
    }
}
