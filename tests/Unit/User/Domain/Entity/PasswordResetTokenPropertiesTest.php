<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Factory\PasswordResetTokenFactory;
use App\User\Domain\Factory\PasswordResetTokenFactoryInterface;

final class PasswordResetTokenPropertiesTest extends UnitTestCase
{
    private PasswordResetTokenFactoryInterface $passwordResetTokenFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordResetTokenFactory = new PasswordResetTokenFactory(
            32,
            1
        );
    }

    public function testTokenProperties(): void
    {
        $userID = $this->faker->uuid();
        $token = $this->passwordResetTokenFactory->create($userID);

        $this->assertSame($userID, $token->getUserID());
        $this->assertNotEmpty($token->getTokenValue());
        $this->assertInstanceOf(\DateTimeImmutable::class, $token->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $token->getExpiresAt());
        $this->assertTrue($token->getExpiresAt() > $token->getCreatedAt());
    }

    public function testExtendExpiration(): void
    {
        $originalExpiration = new \DateTimeImmutable('+1 hour');
        $newExpiration = new \DateTimeImmutable('+2 hours');

        $token = new PasswordResetToken(
            'test-token',
            $this->faker->uuid(),
            $originalExpiration,
            new \DateTimeImmutable()
        );

        $this->assertSame($originalExpiration, $token->getExpiresAt());

        $token->extendExpiration($newExpiration);

        $this->assertSame($newExpiration, $token->getExpiresAt());
    }

    public function testExtendExpirationMakesExpiredTokenValid(): void
    {
        $expiredTime = new \DateTimeImmutable('-1 hour');
        $token = new PasswordResetToken(
            'expired-token',
            $this->faker->uuid(),
            $expiredTime,
            new \DateTimeImmutable('-2 hours')
        );

        $this->assertTrue($token->isExpired());

        $newExpiration = new \DateTimeImmutable('+1 hour');
        $token->extendExpiration($newExpiration);

        $this->assertFalse($token->isExpired());
    }
}
