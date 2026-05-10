<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PasswordResetToken;

final class PasswordResetTokenExpirationBoundaryTest extends UnitTestCase
{
    public function testMicroSecondBoundaryConditions(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $microTime = new \DateTimeImmutable("+{$i} microseconds");
            $microToken = new PasswordResetToken(
                "micro-token-{$i}",
                $this->faker->uuid(),
                $microTime,
                $microTime->modify('-1 hour')
            );

            $result = $microToken->isExpired();
            $this->assertIsBool($result);
        }
    }

    public function testIsExpiredGreaterThanVsGreaterEqualBoundary(): void
    {
        $expirationTime = new \DateTimeImmutable('2024-01-01 12:00:00.000000');
        $token = new PasswordResetToken(
            'boundary-test-token',
            $this->faker->uuid(),
            $expirationTime,
            $expirationTime->modify('-1 hour')
        );

        $this->assertFalse($token->isExpired($expirationTime));

        $beforeExpiration = $expirationTime->modify('-1 microsecond');
        $this->assertFalse($token->isExpired($beforeExpiration));

        $afterExpiration = $expirationTime->modify('+1 microsecond');
        $this->assertTrue($token->isExpired($afterExpiration));

        $this->assertFalse($token->isExpired($expirationTime->modify('-1 second')));
        $this->assertTrue($token->isExpired($expirationTime->modify('+1 second')));
    }

    public function testIsExpiredWithPreciseTimestamp(): void
    {
        $baseTime = new \DateTimeImmutable('2024-01-01 12:00:00.000000');

        $beforeTime = $baseTime->modify('-1 second');
        $beforeToken = new PasswordResetToken(
            'before-token',
            $this->faker->uuid(),
            $beforeTime,
            $beforeTime->modify('-1 hour')
        );

        $this->assertTrue($beforeToken->isExpired());

        $futureTime = new \DateTimeImmutable('+1 hour');
        $futureToken = new PasswordResetToken(
            'future-token',
            $this->faker->uuid(),
            $futureTime,
            $futureTime->modify('-1 hour')
        );

        $this->assertFalse($futureToken->isExpired());
    }

    public function testIsExpiredStrictGreaterThan(): void
    {
        $expireTime = new \DateTimeImmutable('2024-06-15 14:30:00');
        $token = new PasswordResetToken(
            'strict-boundary-token',
            $this->faker->uuid(),
            $expireTime,
            $expireTime->modify('-1 hour')
        );

        $this->assertNotExpiredAtExactTime($token, $expireTime);
        $this->assertExpiredAfterTime($token, $expireTime);
        $this->assertNotExpiredBeforeTime($token, $expireTime);
    }

    private function assertNotExpiredAtExactTime(
        PasswordResetToken $token,
        \DateTimeImmutable $expireTime
    ): void {
        $this->assertFalse(
            $token->isExpired($expireTime)
        );
    }

    private function assertExpiredAfterTime(
        PasswordResetToken $token,
        \DateTimeImmutable $expireTime
    ): void {
        $justAfter = $expireTime->modify('+1 microsecond');
        $this->assertTrue(
            $token->isExpired($justAfter)
        );
    }

    private function assertNotExpiredBeforeTime(
        PasswordResetToken $token,
        \DateTimeImmutable $expireTime
    ): void {
        $justBefore = $expireTime->modify('-1 microsecond');
        $this->assertFalse(
            $token->isExpired($justBefore)
        );
    }
}
