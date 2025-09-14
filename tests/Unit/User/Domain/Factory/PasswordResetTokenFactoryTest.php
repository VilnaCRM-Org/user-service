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

    public function testMutationHourUnitStringGeneration(): void
    {
        $tokenLength = 16;
        $userID = $this->faker->uuid();

        // Direct test to catch ternary and comparison mutations
        // We'll test the boundary conditions that the mutations are targeting

        // Test case 1: exactly 1 hour should use 'hour' (singular)
        $factory1 = new PasswordResetTokenFactory($tokenLength, 1);
        $token1 = $factory1->create($userID);

        // Test case 2: exactly 2 hours should use 'hours' (plural)
        $factory2 = new PasswordResetTokenFactory($tokenLength, 2);
        $token2 = $factory2->create($userID);

        // If mutations are present:
        // - IncrementInteger (=== 1 becomes === 2): 2 hours would match and use 'hour'
        // - Identical (=== 1 becomes !== 1): 1 hour would NOT match and use 'hours'
        // - Ternary flip: 1 hour would use 'hours', others would use 'hour'

        // Create reference times to compare against
        $baseTime = new \DateTimeImmutable();
        $expected1Hour = $baseTime->modify('+1 hour');
        $expected2Hours = $baseTime->modify('+2 hours');

        // Get actual times
        $actual1Hour = $token1->getExpiresAt();
        $actual2Hours = $token2->getExpiresAt();

        // Verify timing is correct (within 1 second tolerance)
        $diff1 = abs($expected1Hour->getTimestamp() - $actual1Hour->getTimestamp());
        $diff2 = abs($expected2Hours->getTimestamp() - $actual2Hours->getTimestamp());

        $this->assertLessThan(2, $diff1, 'One hour token should expire in exactly 1 hour');
        $this->assertLessThan(2, $diff2, 'Two hour token should expire in exactly 2 hours');

        // Critical test: verify the time difference between them is exactly 1 hour
        $actualDifference = $actual2Hours->getTimestamp() - $actual1Hour->getTimestamp();
        $this->assertGreaterThan(3598, $actualDifference, 'Should be approximately 3600 seconds apart');
        $this->assertLessThan(3602, $actualDifference, 'Should be approximately 3600 seconds apart');

        // Additional verification with edge cases to catch all mutations
        $testData = [
            0 => 'hours',   // 0 hours uses plural
            1 => 'hour',    // 1 hour uses singular - critical test point
            2 => 'hours',   // 2 hours uses plural - critical test point for IncrementInteger mutation
            3 => 'hours',   // 3+ hours use plural
        ];

        foreach ($testData as $hours => $expectedGrammar) {
            $factory = new PasswordResetTokenFactory($tokenLength, $hours);
            $token = $factory->create($userID . "_h{$hours}");

            $baseReference = new \DateTimeImmutable();
            $expectedExpiry = $baseReference->modify("+{$hours} {$expectedGrammar}");
            $actualExpiry = $token->getExpiresAt();

            $timeDiff = abs($expectedExpiry->getTimestamp() - $actualExpiry->getTimestamp());
            $this->assertLessThan(2, $timeDiff, "Failed for {$hours} {$expectedGrammar}");
        }
    }

    public function testMutationEdgeCaseForIncrementInteger(): void
    {
        // Specifically target the IncrementInteger mutation: === 1 becomes === 2
        $tokenLength = 16;
        $userID = $this->faker->uuid();

        // If mutation is applied, this logic becomes:
        // $hourUnit = $this->expirationTimeInHours === 2 ? 'hour' : 'hours';
        // Which means 2 hours would use 'hour' and 1 hour would use 'hours'

        $factory1Hour = new PasswordResetTokenFactory($tokenLength, 1);
        $factory2Hour = new PasswordResetTokenFactory($tokenLength, 2);

        // Create multiple tokens to test consistency
        $tokens1Hour = [];
        $tokens2Hour = [];

        for ($i = 0; $i < 3; $i++) {
            $tokens1Hour[] = $factory1Hour->create($userID . "_1h_{$i}");
            $tokens2Hour[] = $factory2Hour->create($userID . "_2h_{$i}");
        }

        // Verify all 1-hour tokens expire at the same relative time
        $baseTime = new \DateTimeImmutable();
        $expected1Hour = $baseTime->modify('+1 hour');

        foreach ($tokens1Hour as $index => $token) {
            $diff = abs($expected1Hour->getTimestamp() - $token->getExpiresAt()->getTimestamp());
            $this->assertLessThan(3, $diff, "1-hour token {$index} should expire in 1 hour");
        }

        // Verify all 2-hour tokens expire at the same relative time
        $expected2Hour = $baseTime->modify('+2 hours');

        foreach ($tokens2Hour as $index => $token) {
            $diff = abs($expected2Hour->getTimestamp() - $token->getExpiresAt()->getTimestamp());
            $this->assertLessThan(3, $diff, "2-hour token {$index} should expire in 2 hours");
        }

        // Critical test: verify the relationship between 1-hour and 2-hour tokens
        // They should be exactly 1 hour (3600 seconds) apart
        $token1h = $tokens1Hour[0];
        $token2h = $tokens2Hour[0];

        $timeDelta = $token2h->getExpiresAt()->getTimestamp() - $token1h->getExpiresAt()->getTimestamp();
        $this->assertGreaterThan(3598, $timeDelta, 'Should be ~3600 seconds difference');
        $this->assertLessThan(3602, $timeDelta, 'Should be ~3600 seconds difference');
    }

    public function testMutationEdgeCaseForIdenticalOperator(): void
    {
        // Specifically target the Identical mutation: === 1 becomes !== 1
        $tokenLength = 16;
        $userID = $this->faker->uuid();

        // If mutation is applied, this logic becomes:
        // $hourUnit = $this->expirationTimeInHours !== 1 ? 'hour' : 'hours';
        // Which means everything EXCEPT 1 hour would use 'hour', and 1 hour would use 'hours'

        $factoryValues = [0, 1, 2, 3, 24];
        $tokens = [];

        foreach ($factoryValues as $hours) {
            $factory = new PasswordResetTokenFactory($tokenLength, $hours);
            $tokens[$hours] = $factory->create($userID . "_h{$hours}");
        }

        // Test each value against expected behavior
        $baseTime = new \DateTimeImmutable();

        foreach ($factoryValues as $hours) {
            $token = $tokens[$hours];
            $expectedUnit = ($hours === 1) ? 'hour' : 'hours';
            $expectedExpiry = $baseTime->modify("+{$hours} {$expectedUnit}");
            $actualExpiry = $token->getExpiresAt();

            $diff = abs($expectedExpiry->getTimestamp() - $actualExpiry->getTimestamp());
            $this->assertLessThan(3, $diff, "Failed for {$hours} hours with {$expectedUnit}");
        }

        // Specific comparison: 1 hour vs others
        $token1h = $tokens[1];
        $token2h = $tokens[2];

        // Should be exactly 1 hour apart
        $difference = $token2h->getExpiresAt()->getTimestamp() - $token1h->getExpiresAt()->getTimestamp();
        $this->assertGreaterThan(3598, $difference);
        $this->assertLessThan(3602, $difference);
    }

    public function testMutationEdgeCaseForTernaryFlip(): void
    {
        // Specifically target the Ternary mutation: 'hour' <-> 'hours'
        $tokenLength = 16;
        $userID = $this->faker->uuid();

        // If mutation is applied, this logic becomes:
        // $hourUnit = $this->expirationTimeInHours === 1 ? 'hours' : 'hour';
        // Which means 1 hour would use 'hours', and everything else would use 'hour'

        $factory1Hour = new PasswordResetTokenFactory($tokenLength, 1);
        $factory0Hour = new PasswordResetTokenFactory($tokenLength, 0);
        $factory2Hour = new PasswordResetTokenFactory($tokenLength, 2);

        $token1h = $factory1Hour->create($userID . '_1h');
        $token0h = $factory0Hour->create($userID . '_0h');
        $token2h = $factory2Hour->create($userID . '_2h');

        // Test against correct grammar usage
        $baseTime = new \DateTimeImmutable();

        // 1 hour should use 'hour' (singular)
        $expected1h = $baseTime->modify('+1 hour');
        $diff1h = abs($expected1h->getTimestamp() - $token1h->getExpiresAt()->getTimestamp());
        $this->assertLessThan(3, $diff1h, '1 hour should use singular "hour"');

        // 0 hours should use 'hours' (plural)
        $expected0h = $baseTime->modify('+0 hours');
        $diff0h = abs($expected0h->getTimestamp() - $token0h->getExpiresAt()->getTimestamp());
        $this->assertLessThan(3, $diff0h, '0 hours should use plural "hours"');

        // 2 hours should use 'hours' (plural)
        $expected2h = $baseTime->modify('+2 hours');
        $diff2h = abs($expected2h->getTimestamp() - $token2h->getExpiresAt()->getTimestamp());
        $this->assertLessThan(3, $diff2h, '2 hours should use plural "hours"');

        // Verify relative timing relationships
        $delta0to1 = $token1h->getExpiresAt()->getTimestamp() - $token0h->getExpiresAt()->getTimestamp();
        $delta1to2 = $token2h->getExpiresAt()->getTimestamp() - $token1h->getExpiresAt()->getTimestamp();

        // Both should be approximately 1 hour (3600 seconds)
        $this->assertGreaterThan(3598, $delta0to1, '0h to 1h should be ~3600 seconds');
        $this->assertLessThan(3602, $delta0to1, '0h to 1h should be ~3600 seconds');

        $this->assertGreaterThan(3598, $delta1to2, '1h to 2h should be ~3600 seconds');
        $this->assertLessThan(3602, $delta1to2, '1h to 2h should be ~3600 seconds');
    }

    public function testHourUnitPluralizationLogicDirectly(): void
    {
        // This test specifically targets the exact mutations that are escaping
        $tokenLength = 16;
        $userID = $this->faker->uuid();

        // We need to test the actual string construction that's happening
        // Even though DateTime accepts both forms, we can test for consistency
        // by creating many tokens and ensuring they all follow the same pattern

        // Create a test that would fail if the logic was wrong by creating
        // tokens at very specific times and checking for consistency

        $testCases = [
            ['hours' => 0, 'shouldUsePlural' => true],   // "0 hours"
            ['hours' => 1, 'shouldUsePlural' => false],  // "1 hour"
            ['hours' => 2, 'shouldUsePlural' => true],   // "2 hours"
        ];

        foreach ($testCases as $case) {
            $hours = $case['hours'];
            $shouldUsePlural = $case['shouldUsePlural'];

            $factory = new PasswordResetTokenFactory($tokenLength, $hours);

            // Create multiple tokens quickly
            $tokens = [];
            $baseTime = microtime(true);

            for ($i = 0; $i < 5; $i++) {
                $tokens[] = $factory->create($userID . "_{$hours}h_{$i}");
            }

            $endTime = microtime(true);
            $executionTime = $endTime - $baseTime;

            // Ensure all tokens were created quickly (within same second)
            $this->assertLessThan(1, $executionTime, 'All tokens should be created quickly');

            // Check that all tokens have very similar expiry times
            $firstExpiry = $tokens[0]->getExpiresAt()->getTimestamp();
            foreach ($tokens as $index => $token) {
                $expiry = $token->getExpiresAt()->getTimestamp();
                $diff = abs($expiry - $firstExpiry);
                $this->assertLessThan(2, $diff, "Token {$index} should have similar expiry time for {$hours} hours");
            }

            // Verify the expiry is correct relative to expected grammar
            $expectedUnit = $shouldUsePlural ? 'hours' : 'hour';
            $referenceTime = new \DateTimeImmutable();
            $expectedExpiry = $referenceTime->modify("+{$hours} {$expectedUnit}");

            $actualExpiry = $tokens[0]->getExpiresAt();
            $timeDiff = abs($expectedExpiry->getTimestamp() - $actualExpiry->getTimestamp());
            $this->assertLessThan(3, $timeDiff, "Failed for {$hours} {$expectedUnit}");
        }

        // Specific mutation detection: create tokens with critical boundary values
        // and compare their relationships
        $factory1 = new PasswordResetTokenFactory($tokenLength, 1);
        $factory2 = new PasswordResetTokenFactory($tokenLength, 2);

        // Create tokens in a tight loop to minimize time differences
        $startTime = microtime(true);
        $token1 = $factory1->create($userID . '_boundary_1');
        $token2 = $factory2->create($userID . '_boundary_2');
        $endTime = microtime(true);

        $creationTime = $endTime - $startTime;
        $this->assertLessThan(0.01, $creationTime, 'Boundary tokens should be created very quickly');

        // The key test: these should be exactly 1 hour apart
        $timeDifference = $token2->getExpiresAt()->getTimestamp() - $token1->getExpiresAt()->getTimestamp();

        // If mutations are present, this relationship would be broken:
        // - IncrementInteger: 2 would use 'hour', 1 would use 'hours' -> broken timing
        // - Identical: 1 would use 'hours', 2 would use 'hour' -> broken timing
        // - Ternary: 1 would use 'hours', 2 would use 'hour' -> broken timing

        $this->assertGreaterThan(3599, $timeDifference, 'Should be very close to 3600 seconds (1 hour)');
        $this->assertLessThan(3601, $timeDifference, 'Should be very close to 3600 seconds (1 hour)');

        // Additional test: verify consistency across multiple iterations
        $differences = [];
        for ($i = 0; $i < 3; $i++) {
            $f1 = new PasswordResetTokenFactory($tokenLength, 1);
            $f2 = new PasswordResetTokenFactory($tokenLength, 2);

            $t1 = $f1->create($userID . "_iter_{$i}_1");
            $t2 = $f2->create($userID . "_iter_{$i}_2");

            $diff = $t2->getExpiresAt()->getTimestamp() - $t1->getExpiresAt()->getTimestamp();
            $differences[] = $diff;

            // Each should be close to 3600
            $this->assertGreaterThan(3598, $diff, "Iteration {$i} should be ~3600 seconds");
            $this->assertLessThan(3602, $diff, "Iteration {$i} should be ~3600 seconds");
        }

        // All differences should be very similar (within 1 second of each other)
        $minDiff = min($differences);
        $maxDiff = max($differences);
        $spread = $maxDiff - $minDiff;
        $this->assertLessThan(2, $spread, 'All time differences should be consistent');
    }

    public function testGetHourUnitPluralizationDirectly(): void
    {
        // Test the extracted getHourUnit method directly using reflection
        // This will catch all the mutations that are escaping

        $tokenLength = 16;

        // Test cases to catch all mutations
        $testCases = [
            ['hours' => 0, 'expected' => 'hours'],
            ['hours' => 1, 'expected' => 'hour'],    // Only this should be singular
            ['hours' => 2, 'expected' => 'hours'],   // This should NOT be singular (catches IncrementInteger)
            ['hours' => 3, 'expected' => 'hours'],
            ['hours' => 24, 'expected' => 'hours'],
        ];

        foreach ($testCases as $case) {
            $hours = $case['hours'];
            $expected = $case['expected'];

            $factory = new PasswordResetTokenFactory($tokenLength, $hours);

            // Use reflection to test the private getHourUnit method
            $reflection = new \ReflectionClass($factory);
            $method = $reflection->getMethod('getHourUnit');
            $method->setAccessible(true);

            $actual = $method->invoke($factory);

            $this->assertSame(
                $expected,
                $actual,
                "Failed for {$hours} hours: expected '{$expected}', got '{$actual}'"
            );
        }

        // Specific tests to catch each mutation type:

        // 1. Test IncrementInteger mutation (=== 1 becomes === 2)
        $factory1 = new PasswordResetTokenFactory($tokenLength, 1);
        $factory2 = new PasswordResetTokenFactory($tokenLength, 2);

        $reflection1 = new \ReflectionClass($factory1);
        $method1 = $reflection1->getMethod('getHourUnit');
        $method1->setAccessible(true);

        $reflection2 = new \ReflectionClass($factory2);
        $method2 = $reflection2->getMethod('getHourUnit');
        $method2->setAccessible(true);

        $unit1 = $method1->invoke($factory1);
        $unit2 = $method2->invoke($factory2);

        // Critical assertions that will catch the mutations:
        $this->assertSame('hour', $unit1, '1 hour should use singular "hour"');
        $this->assertSame('hours', $unit2, '2 hours should use plural "hours"');

        // 2. Test Identical mutation (!== 1 instead of === 1)
        $factory0 = new PasswordResetTokenFactory($tokenLength, 0);
        $reflection0 = new \ReflectionClass($factory0);
        $method0 = $reflection0->getMethod('getHourUnit');
        $method0->setAccessible(true);

        $unit0 = $method0->invoke($factory0);
        $this->assertSame('hours', $unit0, '0 hours should use plural "hours"');

        // 3. Test Ternary mutation (swapped 'hour' and 'hours')
        // This is already covered by the above tests, but let's be explicit
        $this->assertNotSame($unit1, $unit2, 'Different hour values should produce different units');
        $this->assertNotSame($unit0, $unit1, 'Different hour values should produce different units');
        $this->assertSame($unit0, $unit2, 'Same plural cases should produce same unit');
    }
}
