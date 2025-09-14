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
        $initials = strtoupper(substr($this->faker->firstName(), 0, 1) . substr($this->faker->lastName(), 0, 1));
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
        $initials = strtoupper(substr($this->faker->firstName(), 0, 1) . substr($this->faker->lastName(), 0, 1));
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
        $initials = strtoupper(substr($this->faker->firstName(), 0, 1) . substr($this->faker->lastName(), 0, 1));
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
        $user = $this->userFactory->create('test4@example.com', 'T4', 'password123', new Uuid('423e4567-e89b-12d3-a456-426614174003'));
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

    public function testCountRecentRequestsByEmail(): void
    {
        $email = 'test5@example.com';
        $user = $this->userFactory->create($email, 'T5', 'password123', new Uuid('523e4567-e89b-12d3-a456-426614174004'));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $since = new \DateTimeImmutable('-2 hours'); // Changed from -1 hour to -2 hours

        // Create tokens for the user
        $createdAt1 = new \DateTimeImmutable();
        $expiresAt1 = $createdAt1->add(new \DateInterval('PT1H'));
        $token1 = new PasswordResetToken('count_token1', $user->getId(), $expiresAt1, $createdAt1);

        $createdAt2 = new \DateTimeImmutable();
        $expiresAt2 = $createdAt2->add(new \DateInterval('PT1H'));
        $token2 = new PasswordResetToken('count_token2', $user->getId(), $expiresAt2, $createdAt2);

        $this->repository->save($token1);
        $this->repository->save($token2);

        // Verify tokens were saved correctly
        $savedToken1 = $this->repository->findByToken('count_token1');
        $savedToken2 = $this->repository->findByToken('count_token2');
        $this->assertNotNull($savedToken1);
        $this->assertNotNull($savedToken2);
        $this->assertSame($user->getId(), $savedToken1->getUserID());
        $this->assertSame($user->getId(), $savedToken2->getUserID());

        $count = $this->repository->countRecentRequestsByEmail($email, $since);
        $this->assertSame(2, $count);
    }

    public function testCountRecentRequestsByEmailNoRequests(): void
    {
        $email = 'test6@example.com';
        $since = new \DateTimeImmutable('-1 hour');

        $count = $this->repository->countRecentRequestsByEmail($email, $since);
        $this->assertSame(0, $count);
    }

    public function testCountRecentRequestsByEmailOldRequests(): void
    {
        $email = 'test7@example.com';
        $user = $this->userFactory->create($email, 'T7', 'password123', new Uuid('623e4567-e89b-12d3-a456-426614174005'));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Count since now (no old requests should be counted)
        $since = new \DateTimeImmutable();

        $count = $this->repository->countRecentRequestsByEmail($email, $since);
        $this->assertSame(0, $count);
    }
}
