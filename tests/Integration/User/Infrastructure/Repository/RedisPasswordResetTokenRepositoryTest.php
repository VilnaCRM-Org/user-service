<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Infrastructure\Repository;

use App\Tests\Integration\IntegrationTestCase;
use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;

final class RedisPasswordResetTokenRepositoryTest extends IntegrationTestCase
{
    private PasswordResetTokenRepositoryInterface $passwordResetTokenRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordResetTokenRepository = $this->container->get(
            PasswordResetTokenRepositoryInterface::class
        );
    }

    public function testSaveAndFind(): void
    {
        $tokenValue = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $token = new PasswordResetToken($tokenValue, $userId);

        $this->passwordResetTokenRepository->save($token);

        $foundToken = $this->passwordResetTokenRepository->find($tokenValue);

        $this->assertInstanceOf(PasswordResetToken::class, $foundToken);
        $this->assertEquals($tokenValue, $foundToken->getTokenValue());
        $this->assertEquals($userId, $foundToken->getUserID());
        $this->assertFalse($foundToken->isExpired());
    }

    public function testFindByUserId(): void
    {
        $tokenValue = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $token = new PasswordResetToken($tokenValue, $userId);

        $this->passwordResetTokenRepository->save($token);

        $foundToken = $this->passwordResetTokenRepository->findByUserId($userId);

        $this->assertInstanceOf(PasswordResetToken::class, $foundToken);
        $this->assertEquals($tokenValue, $foundToken->getTokenValue());
        $this->assertEquals($userId, $foundToken->getUserID());
    }

    public function testDelete(): void
    {
        $tokenValue = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $token = new PasswordResetToken($tokenValue, $userId);

        $this->passwordResetTokenRepository->save($token);

        $this->passwordResetTokenRepository->delete($token);

        $foundToken = $this->passwordResetTokenRepository->find($tokenValue);
        $this->assertNull($foundToken);

        $foundTokenByUserId = $this->passwordResetTokenRepository->findByUserId($userId);
        $this->assertNull($foundTokenByUserId);
    }

    public function testTokenExpiration(): void
    {
        $tokenValue = $this->faker->uuid();
        $userId = $this->faker->uuid();
        // Create token that expires in 1 second
        $token = new PasswordResetToken($tokenValue, $userId, 1);

        $this->passwordResetTokenRepository->save($token);

        // Token should not be expired immediately
        $this->assertFalse($token->isExpired());

        // Sleep for 2 seconds to ensure token expires
        sleep(2);

        // Token should now be expired
        $this->assertTrue($token->isExpired());
    }

    public function testFindReturnsNullForNonExistentToken(): void
    {
        $foundToken = $this->passwordResetTokenRepository->find('non-existent-token');
        $this->assertNull($foundToken);
    }

    public function testFindByUserIdReturnsNullForNonExistentUser(): void
    {
        $foundToken = $this->passwordResetTokenRepository->findByUserId('non-existent-user');
        $this->assertNull($foundToken);
    }
}