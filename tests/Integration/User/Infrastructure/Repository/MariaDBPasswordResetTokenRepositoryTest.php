<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Infrastructure\Repository;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Integration\IntegrationTestCase;
use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Factory\UserFactory;
use App\User\Infrastructure\Repository\MariaDBPasswordResetTokenRepository;
use Doctrine\ORM\EntityManagerInterface;

final class MariaDBPasswordResetTokenRepositoryTest extends IntegrationTestCase
{
    private MariaDBPasswordResetTokenRepository $repository;
    private EntityManagerInterface $entityManager;
    private UserFactory $userFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getContainer()->get(MariaDBPasswordResetTokenRepository::class);
        $this->entityManager = $this->getContainer()->get(EntityManagerInterface::class);
        $this->userFactory = new UserFactory();
    }

    public function testSave(): void
    {
        $email = $this->faker->unique()->email();
        $initials = strtoupper(
            substr($this->faker->firstName(), 0, 1) . substr($this->faker->lastName(), 0, 1)
        );
        $password = $this->faker->password(8);
        $userId = $this->faker->uuid();

        $user = $this->userFactory->create($email, $initials, $password, new Uuid($userId));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->add(new \DateInterval('PT1H'));
        $tokenValue = $this->faker->lexify('??????????');
        $token = new PasswordResetToken($tokenValue, $user->getId(), $expiresAt, $createdAt);

        $this->repository->save($token);

        $found = $this->repository->findByToken($tokenValue);
        $this->assertNotNull($found);
        $this->assertSame($tokenValue, $found->getTokenValue());
        $this->assertSame($user->getId(), $found->getUserID());
    }

    public function testFindByToken(): void
    {
        $email = $this->faker->unique()->email();
        $initials = strtoupper(
            substr($this->faker->firstName(), 0, 1) . substr($this->faker->lastName(), 0, 1)
        );
        $password = $this->faker->password(8);
        $userId = $this->faker->uuid();

        $user = $this->userFactory->create($email, $initials, $password, new Uuid($userId));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->add(new \DateInterval('PT1H'));
        $tokenValue = $this->faker->lexify('??????????');
        $token = new PasswordResetToken($tokenValue, $user->getId(), $expiresAt, $createdAt);
        $this->repository->save($token);

        $found = $this->repository->findByToken($tokenValue);
        $this->assertNotNull($found);
        $this->assertSame($tokenValue, $found->getTokenValue());
        $this->assertSame($user->getId(), $found->getUserID());
    }

    public function testFindByTokenNotFound(): void
    {
        $nonExistentToken = $this->faker->lexify('??????????');
        $found = $this->repository->findByToken($nonExistentToken);
        $this->assertNull($found);
    }

    public function testFindByUserID(): void
    {
        $email = $this->faker->unique()->email();
        $initials = strtoupper(
            substr($this->faker->firstName(), 0, 1) . substr($this->faker->lastName(), 0, 1)
        );
        $password = $this->faker->password(8);
        $userId = $this->faker->uuid();

        $user = $this->userFactory->create($email, $initials, $password, new Uuid($userId));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $baseTime = new \DateTimeImmutable('2023-01-01 10:00:00');
        $createdAt1 = $baseTime;
        $expiresAt1 = $createdAt1->add(new \DateInterval('PT1H'));
        $token1Value = $this->faker->lexify('??????????');
        $token1 = new PasswordResetToken($token1Value, $user->getId(), $expiresAt1, $createdAt1);

        $createdAt2 = $baseTime->add(new \DateInterval('PT1S'));
        $expiresAt2 = $createdAt2->add(new \DateInterval('PT1H'));
        $token2Value = $this->faker->lexify('??????????');
        $token2 = new PasswordResetToken($token2Value, $user->getId(), $expiresAt2, $createdAt2);

        $this->repository->save($token1);
        $this->repository->save($token2);

        $found = $this->repository->findByUserID($user->getId());
        $this->assertNotNull($found);
        $this->assertSame($token2Value, $found->getTokenValue());
    }

    public function testFindByUserIDNotFound(): void
    {
        $nonExistentUserId = $this->faker->uuid();
        $found = $this->repository->findByUserID($nonExistentUserId);
        $this->assertNull($found);
    }

    public function testDelete(): void
    {
        $user = $this->userFactory->create(
            'test4@example.com',
            'T4',
            'password123',
            new Uuid('423e4567-e89b-12d3-a456-426614174003')
        );
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->add(new \DateInterval('PT1H'));
        $token = new PasswordResetToken('delete_token', $user->getId(), $expiresAt, $createdAt);
        $this->repository->save($token);

        // Verify token exists
        $found = $this->repository->findByToken('delete_token');
        $this->assertNotNull($found);

        // Delete token
        $this->repository->delete($token);

        // Verify token is deleted
        $found = $this->repository->findByToken('delete_token');
        $this->assertNull($found);
    }
}
