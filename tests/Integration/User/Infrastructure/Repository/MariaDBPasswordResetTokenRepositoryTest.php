<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Infrastructure\Repository;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Integration\IntegrationTestCase;
use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Entity\User;
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
        $password = $this->faker->password(8);
        $userId = $this->faker->uuid();

        $user = $this->userFactory->create(
            $email,
            $this->initials(),
            $password,
            new Uuid($userId)
        );
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $baseTime = new \DateTimeImmutable('2023-01-01 10:00:00');
        $token1 = $this->tokenFor($user, $baseTime);
        $token2 = $this->tokenFor($user, $baseTime->add(new \DateInterval('PT1S')));

        $this->repository->save($token1);
        $this->repository->save($token2);

        $found = $this->repository->findByUserID($user->getId());
        $this->assertNotNull($found);
        $this->assertSame($token2->getTokenValue(), $found->getTokenValue());
    }

    public function testFindByUserIDNotFound(): void
    {
        $nonExistentUserId = $this->faker->uuid();
        $found = $this->repository->findByUserID($nonExistentUserId);
        $this->assertNull($found);
    }

    public function testDelete(): void
    {
        $user = $this->createAndPersistUser();
        $token = $this->createAndSaveToken($user->getId());

        $found = $this->repository->findByToken($token->getTokenValue());
        $this->assertNotNull($found);

        $this->repository->delete($token);

        $found = $this->repository->findByToken($token->getTokenValue());
        $this->assertNull($found);
    }

    public function testDeleteAll(): void
    {
        $user = $this->createAndPersistUser();
        $token1 = $this->createAndSaveToken($user->getId());
        $token2 = $this->createAndSaveToken($user->getId());

        $this->assertNotNull($this->repository->findByToken($token1->getTokenValue()));
        $this->assertNotNull($this->repository->findByToken($token2->getTokenValue()));

        $this->repository->deleteAll();

        $this->assertNull($this->repository->findByToken($token1->getTokenValue()));
        $this->assertNull($this->repository->findByToken($token2->getTokenValue()));
    }

    private function createAndPersistUser(): User
    {
        $user = $this->userFactory->create(
            $this->faker->unique()->email(),
            $this->initials(),
            $this->faker->password(8),
            new Uuid($this->faker->uuid())
        );
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createAndSaveToken(string $userId): PasswordResetToken
    {
        $createdAt = new \DateTimeImmutable();
        $expiresAt = $createdAt->add(new \DateInterval('PT1H'));
        $token = new PasswordResetToken(
            $this->faker->lexify('??????????'),
            $userId,
            $expiresAt,
            $createdAt
        );
        $this->repository->save($token);

        return $token;
    }

    private function initials(): string
    {
        return strtoupper(
            substr($this->faker->firstName(), 0, 1) . substr($this->faker->lastName(), 0, 1)
        );
    }

    private function tokenFor(User $user, \DateTimeImmutable $createdAt): PasswordResetToken
    {
        $expiresAt = $createdAt->add(new \DateInterval('PT1H'));
        $tokenValue = $this->faker->lexify('??????????');

        return new PasswordResetToken($tokenValue, $user->getId(), $expiresAt, $createdAt);
    }
}
