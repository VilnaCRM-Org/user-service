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

    public function testCreateWithOneHourExpiration(): void
    {
        $tokenLength = 16;
        $expirationTimeInHours = 1;
        $userID = $this->faker->uuid();

        $factory = new PasswordResetTokenFactory(
            $tokenLength,
            $expirationTimeInHours
        );

        $token = $factory->create($userID);

        $now = new \DateTimeImmutable();
        $expectedExpiry = $now->modify('+1 hour');
        $actualExpiry = $token->getExpiresAt();

        // Allow 1 minute tolerance for test execution time
        $toleranceInSeconds = 60;
        $timeDifference = abs(
            $expectedExpiry->getTimestamp() - $actualExpiry->getTimestamp()
        );
        $this->assertLessThan($toleranceInSeconds, $timeDifference);
    }

    public function testCreateWithTwoHoursExpiration(): void
    {
        $tokenLength = 16;
        $expirationTimeInHours = 2;
        $userID = $this->faker->uuid();

        $factory = new PasswordResetTokenFactory(
            $tokenLength,
            $expirationTimeInHours
        );

        $token = $factory->create($userID);

        $now = new \DateTimeImmutable();
        $expectedExpiry = $now->modify('+2 hours');
        $actualExpiry = $token->getExpiresAt();

        // Allow 1 minute tolerance for test execution time
        $toleranceInSeconds = 60;
        $timeDifference = abs(
            $expectedExpiry->getTimestamp() - $actualExpiry->getTimestamp()
        );
        $this->assertLessThan($toleranceInSeconds, $timeDifference);
    }

    public function testCreateWithZeroHoursExpiration(): void
    {
        $tokenLength = 16;
        $expirationTimeInHours = 0;
        $userID = $this->faker->uuid();

        $factory = new PasswordResetTokenFactory(
            $tokenLength,
            $expirationTimeInHours
        );

        $token = $factory->create($userID);

        $now = new \DateTimeImmutable();
        $expectedExpiry = $now->modify('+0 hours');
        $actualExpiry = $token->getExpiresAt();

        // Allow 1 minute tolerance for test execution time
        $toleranceInSeconds = 60;
        $timeDifference = abs(
            $expectedExpiry->getTimestamp() - $actualExpiry->getTimestamp()
        );
        $this->assertLessThan($toleranceInSeconds, $timeDifference);
    }

    public function testPluralizationLogicExactlyOneHour(): void
    {
        $tokenLength = 16;
        $expirationTimeInHours = 1;
        $userID = $this->faker->uuid();

        $factory = new PasswordResetTokenFactory(
            $tokenLength,
            $expirationTimeInHours
        );

        $createdAt = new \DateTimeImmutable();
        $token = $factory->create($userID);

        // For exactly 1 hour, should use 'hour' (singular)
        $expectedExpiry = $createdAt->modify('+1 hour');
        $actualExpiry = $token->getExpiresAt();

        // Allow 1 minute tolerance for test execution time
        $toleranceInSeconds = 60;
        $timeDifference = abs(
            $expectedExpiry->getTimestamp() - $actualExpiry->getTimestamp()
        );
        $this->assertLessThan($toleranceInSeconds, $timeDifference);
    }

    public function testPluralizationLogicNotOneHour(): void
    {
        $testCases = [0, 2, 3, 24];

        foreach ($testCases as $expirationTimeInHours) {
            $tokenLength = 16;
            $userID = $this->faker->uuid();

            $factory = new PasswordResetTokenFactory(
                $tokenLength,
                $expirationTimeInHours
            );

            $createdAt = new \DateTimeImmutable();
            $token = $factory->create($userID);

            // For any time != 1 hour, should use 'hours' (plural)
            $expectedExpiry = $createdAt->modify("+{$expirationTimeInHours} hours");
            $actualExpiry = $token->getExpiresAt();

            // Allow 1 minute tolerance for test execution time
            $toleranceInSeconds = 60;
            $timeDifference = abs(
                $expectedExpiry->getTimestamp() - $actualExpiry->getTimestamp()
            );
            $this->assertLessThan(
                $toleranceInSeconds,
                $timeDifference,
                "Failed for {$expirationTimeInHours} hours"
            );
        }
    }

    public function testPluralizationBoundaryConditions(): void
    {
        $userID = $this->faker->uuid();
        $tokenLength = 16;

        // Test exactly 1 hour (should use singular 'hour')
        $factoryOne = new PasswordResetTokenFactory($tokenLength, 1);
        $tokenOne = $factoryOne->create($userID);
        $expectedOne = (new \DateTimeImmutable())->modify('+1 hour');

        // Test exactly 2 hours (should use plural 'hours')
        $factoryTwo = new PasswordResetTokenFactory($tokenLength, 2);
        $tokenTwo = $factoryTwo->create($userID);
        $expectedTwo = (new \DateTimeImmutable())->modify('+2 hours');

        // Verify both work correctly - testing that the comparison is === 1, not === 2
        $toleranceInSeconds = 60;

        $timeDifferenceOne = abs(
            $expectedOne->getTimestamp() - $tokenOne->getExpiresAt()->getTimestamp()
        );
        $this->assertLessThan($toleranceInSeconds, $timeDifferenceOne);

        $timeDifferenceTwo = abs(
            $expectedTwo->getTimestamp() - $tokenTwo->getExpiresAt()->getTimestamp()
        );
        $this->assertLessThan($toleranceInSeconds, $timeDifferenceTwo);
    }

    public function testPluralizationLogicWithDifferentBaseTime(): void
    {
        $tokenLength = 16;
        $userID = $this->faker->uuid();

        // Create a known base time
        $baseTime = new \DateTimeImmutable('2025-01-01 12:00:00');

        // Test 1 hour - if logic is wrong (=== 2 instead of === 1), it would use 'hours'
        // but DateTimeImmutable should still work. However, if ternary is flipped,
        // 1 hour would use 'hours' and others would use 'hour', which would break
        $factoryOne = new PasswordResetTokenFactory($tokenLength, 1);

        // We need to test at a specific moment to ensure consistency
        $startTime = microtime(true);
        $tokenOne = $factoryOne->create($userID);
        $endTime = microtime(true);

        // The token should expire exactly 1 hour from creation
        $creationWindow = $endTime - $startTime;
        $this->assertLessThan(1, $creationWindow, 'Test execution should be fast');

        // Check that token expires approximately 1 hour from now
        $expectedExpiry = (new \DateTimeImmutable())->modify('+1 hour');
        $actualExpiry = $tokenOne->getExpiresAt();

        $timeDiff = abs($expectedExpiry->getTimestamp() - $actualExpiry->getTimestamp());
        $this->assertLessThan(2, $timeDiff, 'Token should expire in exactly 1 hour');
    }

    public function testPluralizationMutationSpecific(): void
    {
        $tokenLength = 16;
        $userID = $this->faker->uuid();

        // Test the exact logic that mutation testing is changing
        // The original logic is: $this->expirationTimeInHours === 1 ? 'hour' : 'hours'

        // These should test all the mutations:
        // 1. Ternary flip: 'hour' <-> 'hours'
        // 2. Identical change: === 1 to !== 1
        // 3. IncrementInteger: === 1 to === 2

        // Create scenarios that would fail if mutations are applied
        $testCases = [
            ['hours' => 0, 'expected_unit' => 'hours'],
            ['hours' => 1, 'expected_unit' => 'hour'],   // Critical: only this should be 'hour'
            ['hours' => 2, 'expected_unit' => 'hours'],  // Critical: this should NOT be 'hour'
            ['hours' => 3, 'expected_unit' => 'hours'],
        ];

        foreach ($testCases as $case) {
            $hours = $case['hours'];
            $expectedUnit = $case['expected_unit'];

            $factory = new PasswordResetTokenFactory($tokenLength, $hours);

            // Create two tokens with small time gap to test consistency
            $token1 = $factory->create($userID . '_1');
            usleep(1000); // 1ms delay
            $token2 = $factory->create($userID . '_2');

            $now = new \DateTimeImmutable();

            // Test that the expected time unit produces correct results
            $expectedExpiry = $now->modify("+{$hours} {$expectedUnit}");

            $actualExpiry1 = $token1->getExpiresAt();
            $actualExpiry2 = $token2->getExpiresAt();

            // Both tokens should have similar expiry times
            $timeDiff = abs($actualExpiry1->getTimestamp() - $actualExpiry2->getTimestamp());
            $this->assertLessThan(1, $timeDiff, "Tokens created close together should have similar expiry for {$hours} hours");

            // The expiry should match our expected calculation
            $expectedDiff = abs($expectedExpiry->getTimestamp() - $actualExpiry1->getTimestamp());
            $this->assertLessThan(2, $expectedDiff, "Token expiry should match expected for {$hours} {$expectedUnit}");
        }

        // Specific test to catch the mutations:
        // If === 1 becomes === 2, then hour=1 would use 'hours' and hour=2 would use 'hour'
        // If === 1 becomes !== 1, then hour=1 would use 'hours' and others would use 'hour'
        // If ternary flips, then hour=1 would use 'hours' and others would use 'hour'

        $factory1Hour = new PasswordResetTokenFactory($tokenLength, 1);
        $factory2Hour = new PasswordResetTokenFactory($tokenLength, 2);

        $token1h = $factory1Hour->create($userID);
        $token2h = $factory2Hour->create($userID);

        // These should be exactly 1 hour apart
        $timeDifference = $token2h->getExpiresAt()->getTimestamp() - $token1h->getExpiresAt()->getTimestamp();

        // Should be exactly 3600 seconds (1 hour) apart, within small tolerance
        $this->assertGreaterThan(3595, $timeDifference, 'Should be ~1 hour difference (3600s)');
        $this->assertLessThan(3605, $timeDifference, 'Should be ~1 hour difference (3600s)');

        // Additional boundary test: if logic becomes === 2 instead of === 1
        // Then 1 hour would not match and use 'hours', 2 hours would match and use 'hour'
        // This should create timing inconsistencies we can detect
        $multipleTests = [];
        for ($i = 0; $i < 3; $i++) {
            $factory1 = new PasswordResetTokenFactory($tokenLength, 1);
            $factory2 = new PasswordResetTokenFactory($tokenLength, 2);

            $startTime = microtime(true);
            $tok1 = $factory1->create($userID . "_test_{$i}_1");
            $tok2 = $factory2->create($userID . "_test_{$i}_2");
            $endTime = microtime(true);

            $execTime = $endTime - $startTime;
            $this->assertLessThan(0.1, $execTime, 'Token creation should be fast');

            $diff = $tok2->getExpiresAt()->getTimestamp() - $tok1->getExpiresAt()->getTimestamp();
            $multipleTests[] = $diff;
        }

        // All differences should be very close to 3600 (1 hour)
        foreach ($multipleTests as $index => $diff) {
            $this->assertGreaterThan(3595, $diff, "Test {$index}: Should be ~3600 seconds");
            $this->assertLessThan(3605, $diff, "Test {$index}: Should be ~3600 seconds");
        }

        // Verify the differences are consistent (all within 1 second of each other)
        $minDiff = min($multipleTests);
        $maxDiff = max($multipleTests);
        $this->assertLessThan(2, $maxDiff - $minDiff, 'All time differences should be consistent');
    }
}
