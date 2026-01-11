<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Infrastructure\Repository;

use App\Tests\Integration\IntegrationTestCase;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Repository\TokenRepositoryInterface;

final class RedisTokenRepositoryTest extends IntegrationTestCase
{
    private TokenRepositoryInterface $tokenRepository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenRepository = $this->container->get(
            TokenRepositoryInterface::class
        );
    }

    public function testSaveAndFind(): void
    {
        $tokenValue = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $token = new ConfirmationToken($tokenValue, $userId);

        $this->tokenRepository->save($token);

        $foundToken = $this->tokenRepository->find($tokenValue);

        $this->assertInstanceOf(ConfirmationToken::class, $foundToken);
        $this->assertEquals($tokenValue, $foundToken->getTokenValue());
        $this->assertEquals($userId, $foundToken->getUserID());
    }

    public function testFindByUserId(): void
    {
        $tokenValue = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $token = new ConfirmationToken($tokenValue, $userId);

        $this->tokenRepository->save($token);

        $foundToken = $this->tokenRepository->findByUserId($userId);

        $this->assertInstanceOf(ConfirmationToken::class, $foundToken);
        $this->assertEquals($tokenValue, $foundToken->getTokenValue());
        $this->assertEquals($userId, $foundToken->getUserID());
    }

    public function testDelete(): void
    {
        $tokenValue = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $token = new ConfirmationToken($tokenValue, $userId);

        $this->tokenRepository->save($token);

        $this->tokenRepository->delete($token);

        $foundToken = $this->tokenRepository->find($tokenValue);
        $this->assertNull($foundToken);

        $foundTokenByUserId = $this->tokenRepository->findByUserId($userId);
        $this->assertNull($foundTokenByUserId);
    }
}
