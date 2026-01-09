<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Infrastructure\Repository;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Integration\IntegrationTestCase;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

final class MariaDBUserRepositoryTest extends IntegrationTestCase
{
    private UserRepositoryInterface $repository;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->container->get(
            UserRepositoryInterface::class
        );
        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer(new UuidFactory());
    }

    public function testSave(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userId = $this->faker->uuid();

        $user = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->transformer->transformFromString($userId)
        );

        $this->repository->save($user);

        $savedUser = $this->repository->findByEmail($email);
        $this->assertInstanceOf(UserInterface::class, $savedUser);
        $this->assertSame($initials, $savedUser->getInitials());
        $this->assertSame($password, $savedUser->getPassword());
    }

    public function testDelete(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userId = $this->faker->uuid();

        $user = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->transformer->transformFromString($userId)
        );
        $this->repository->save($user);

        $this->repository->delete($user);

        $foundUser = $this->repository->findByEmail($email);
        $this->assertNull($foundUser);
    }

    public function testFindByEmail(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userId = $this->faker->uuid();

        $user = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->transformer->transformFromString($userId)
        );

        $this->repository->save($user);

        $foundUser = $this->repository->findByEmail($email);

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertSame($initials, $foundUser->getInitials());
        $this->assertSame($password, $foundUser->getPassword());
    }

    public function testFindByEmailNotFound(): void
    {
        $foundUser = $this->repository->findByEmail($this->faker->email());

        $this->assertNull($foundUser);
    }

    public function testFindById(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userId = $this->faker->uuid();

        $user = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->transformer->transformFromString($userId)
        );

        $this->repository->save($user);

        $foundUser = $this->repository->findById($userId);

        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertSame($initials, $foundUser->getInitials());
        $this->assertSame($password, $foundUser->getPassword());
        $this->assertSame($email, $foundUser->getEmail());
    }

    public function testFindByIdNotFound(): void
    {
        $foundUser = $this->repository->findById($this->faker->uuid());

        $this->assertNull($foundUser);
    }

    public function testDeleteAll(): void
    {
        $email1 = $this->faker->email();
        $email2 = $this->faker->email();

        $user1 = $this->userFactory->create(
            $email1,
            $this->faker->name(),
            $this->faker->password(),
            $this->transformer->transformFromString($this->faker->uuid())
        );
        $user2 = $this->userFactory->create(
            $email2,
            $this->faker->name(),
            $this->faker->password(),
            $this->transformer->transformFromString($this->faker->uuid())
        );

        $this->repository->save($user1);
        $this->repository->save($user2);

        $this->repository->deleteAll();

        $this->assertNull($this->repository->findByEmail($email1));
        $this->assertNull($this->repository->findByEmail($email2));
    }
}
