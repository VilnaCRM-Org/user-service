<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Factory\PasswordResetTokenFactory;
use App\User\Domain\Factory\PasswordResetTokenFactoryInterface;

final class PasswordResetTokenExpirationBasicTest extends UnitTestCase
{
    private PasswordResetToken $passwordResetToken;
    private PasswordResetTokenFactoryInterface $passwordResetTokenFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordResetTokenFactory = new PasswordResetTokenFactory(
            32,
            1
        );
        $this->passwordResetToken = $this->passwordResetTokenFactory->create($this->faker->uuid());
    }

    public function testCreatedTokenIsNotExpired(): void
    {
        $this->assertFalse($this->passwordResetToken->isExpired());
    }

    public function testTokenExpiration(): void
    {
        $expiredTime = new \DateTimeImmutable('-2 hours');
        $createdTime = new \DateTimeImmutable('-3 hours');

        $expiredToken = new PasswordResetToken(
            'test-token',
            $this->faker->uuid(),
            $expiredTime,
            $createdTime
        );

        $this->assertTrue($expiredToken->isExpired());
    }

    public function testFutureTokenNotExpired(): void
    {
        $futureTime = new \DateTimeImmutable('+1 year');
        $futureToken = new PasswordResetToken(
            'future-token',
            $this->faker->uuid(),
            $futureTime,
            $futureTime->modify('-1 hour')
        );

        $this->assertFalse($futureToken->isExpired());
    }

    public function testPastTokenExpired(): void
    {
        $pastTime = new \DateTimeImmutable('-1 year');
        $pastToken = new PasswordResetToken(
            'past-token',
            $this->faker->uuid(),
            $pastTime,
            $pastTime->modify('-1 hour')
        );

        $this->assertTrue($pastToken->isExpired());
    }

    public function testIsExpiredUsesProvidedCurrentTimeWhenEarlierThanExpiration(): void
    {
        $expirationTime = new \DateTimeImmutable('2024-01-01 12:00:00');
        $providedCurrentTime = new \DateTimeImmutable('2023-12-31 12:00:00');

        $token = new PasswordResetToken(
            $this->faker->sha256(),
            $this->faker->uuid(),
            $expirationTime,
            $providedCurrentTime->modify('-1 hour')
        );

        $this->assertFalse(
            $token->isExpired($providedCurrentTime)
        );
    }

    public function testIsExpiredRespectsProvidedCurrentTimeParameter(): void
    {
        $expirationTime = new \DateTimeImmutable('2024-01-01 12:00:00');
        $token = new PasswordResetToken(
            'param-test-token',
            $this->faker->uuid(),
            $expirationTime,
            $expirationTime->modify('-1 hour')
        );

        $beforeTime = new \DateTimeImmutable('2024-01-01 11:00:00');
        $this->assertFalse(
            $token->isExpired($beforeTime)
        );

        $afterTime = new \DateTimeImmutable('2024-01-01 13:00:00');
        $this->assertTrue(
            $token->isExpired($afterTime)
        );
    }

    public function testIsExpiredWithoutParameterUsesCurrentTime(): void
    {
        $futureExpiration = new \DateTimeImmutable('+10 years');
        $futureToken = new PasswordResetToken(
            'future-token',
            $this->faker->uuid(),
            $futureExpiration,
            new \DateTimeImmutable()
        );

        $this->assertFalse($futureToken->isExpired());

        $pastExpiration = new \DateTimeImmutable('-10 years');
        $pastToken = new PasswordResetToken(
            'past-token',
            $this->faker->uuid(),
            $pastExpiration,
            $pastExpiration->modify('-1 hour')
        );

        $this->assertTrue($pastToken->isExpired());
    }
}
