<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Factory\PasswordResetTokenFactory;

final class PasswordResetTokenFactoryTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $tokenLength = 16;
        $expirationTimeInHours = 2;
        $userID = $this->faker->uuid();

        $factory = new PasswordResetTokenFactory(
            $tokenLength,
            $expirationTimeInHours
        );

        $token = $factory->create($userID);

        $this->assertInstanceOf(PasswordResetToken::class, $token);
        $this->assertSame($userID, $token->getUserID());
        // bin2hex doubles the length
        $this->assertSame(32, strlen($token->getTokenValue()));
        $this->assertFalse($token->isUsed());
        $this->assertFalse($token->isExpired());

        // Verify the expiration time is set correctly
        $now = new \DateTimeImmutable();
        $expectedExpiry = $now->modify("+{$expirationTimeInHours} hours");
        $actualExpiry = $token->getExpiresAt();

        // Allow 1 minute tolerance for test execution time
        $toleranceInSeconds = 60;
        $timeDifference = abs(
            $expectedExpiry->getTimestamp() - $actualExpiry->getTimestamp()
        );
        $this->assertLessThan($toleranceInSeconds, $timeDifference);
    }

    public function testCreateWithDifferentTokenLength(): void
    {
        $tokenLength = 8;
        $expirationTimeInHours = 1;
        $userID = $this->faker->uuid();

        $factory = new PasswordResetTokenFactory(
            $tokenLength,
            $expirationTimeInHours
        );

        $token = $factory->create($userID);

        // bin2hex doubles the length
        $this->assertSame(16, strlen($token->getTokenValue()));
    }

    public function testCreateWithDifferentExpirationTime(): void
    {
        $tokenLength = 16;
        $expirationTimeInHours = 24;
        $userID = $this->faker->uuid();

        $factory = new PasswordResetTokenFactory(
            $tokenLength,
            $expirationTimeInHours
        );

        $token = $factory->create($userID);

        $now = new \DateTimeImmutable();
        $expectedExpiry = $now->modify("+{$expirationTimeInHours} hours");
        $actualExpiry = $token->getExpiresAt();

        // Allow 1 minute tolerance for test execution time
        $toleranceInSeconds = 60;
        $timeDifference = abs(
            $expectedExpiry->getTimestamp() - $actualExpiry->getTimestamp()
        );
        $this->assertLessThan($toleranceInSeconds, $timeDifference);
    }

    public function testCreateMultipleTokensAreUnique(): void
    {
        $tokenLength = 16;
        $expirationTimeInHours = 1;
        $userID = $this->faker->uuid();

        $factory = new PasswordResetTokenFactory(
            $tokenLength,
            $expirationTimeInHours
        );

        $token1 = $factory->create($userID);
        $token2 = $factory->create($userID);

        $this->assertNotSame(
            $token1->getTokenValue(),
            $token2->getTokenValue()
        );
    }
}
