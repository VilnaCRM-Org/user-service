<?php

declare(strict_types=1);

namespace App\Tests\Functional\User\Infrastructure\Repository;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Tests\Functional\FunctionalTestCase;
use App\User\Application\Exception\DuplicateEmailException;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Repository\MariaDBUserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;

class MariaDBUserRepositoryTest extends FunctionalTestCase
{
    private EntityManager $entityManager;
    private ManagerRegistry $registry;
    private UserRepositoryInterface $repository;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = $this->container->get('doctrine');
        $this->entityManager = $this->registry->getManager();
        $this->repository = new MariaDBUserRepository($this->entityManager, $this->registry);
        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer();
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

    public function testSaveDuplicateEmailException(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();

        $user1 = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->transformer->transformFromString($this->faker->uuid())
        );

        $user2 = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->transformer->transformFromString($this->faker->uuid())
        );

        $this->expectException(DuplicateEmailException::class);

        $this->repository->save($user1);
        $this->repository->save($user2);
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
}
