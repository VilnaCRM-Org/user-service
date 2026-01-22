<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Exception\UserTimedOutException;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;

final class ConfirmationTokenTest extends UnitTestCase
{
    private ConfirmationToken $confirmationToken;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->confirmationTokenFactory = new ConfirmationTokenFactory(
            $this->faker->numberBetween(1, 10)
        );
        $this->confirmationToken =
            $this->confirmationTokenFactory->create($this->faker->uuid());
    }

    public function testSend(): void
    {
        $this->confirmationToken->send();
        $this->confirmationToken->send();
        $this->expectException(UserTimedOutException::class);
        $this->confirmationToken->send();
    }

    public function testSendWithDatetime(): void
    {
        $this->expectException(UserTimedOutException::class);
        $this->confirmationToken->send(
            $this->confirmationToken->getAllowedToSendAfter()
        );
    }

    public function testSetTimesSend(): void
    {
        $num = $this->faker->numberBetween(1, 10);
        $this->confirmationToken->setTimesSent($num);

        $this->assertEquals($num, $this->confirmationToken->getTimesSent());
    }

    public function testSetTokenValue(): void
    {
        $value = $this->faker->uuid();
        $this->confirmationToken->setTokenValue($value);

        $this->assertEquals($value, $this->confirmationToken->getTokenValue());
    }

    public function testSetUserId(): void
    {
        $userId = $this->faker->uuid();
        $this->confirmationToken->setUserID($userId);

        $this->assertEquals($userId, $this->confirmationToken->getUserID());
    }

    public function testSetAllowedToSendAfter(): void
    {
        $allowedToSendAfter = new \DateTimeImmutable();
        $this->confirmationToken->setAllowedToSendAfter($allowedToSendAfter);

        $this->assertEquals(
            $allowedToSendAfter,
            $this->confirmationToken->getAllowedToSendAfter()
        );
    }

    public function testFirstSendWithTimesZeroDefaultsToZeroMinutes(): void
    {
        $sendAt = new \DateTimeImmutable('2024-01-01 12:00:00');

        // timesSent = 0, lookup key 0 => not found => defaults to 0 minutes
        $this->confirmationToken->setTimesSent(0);
        $this->confirmationToken->setAllowedToSendAfter(new \DateTimeImmutable('2024-01-01 11:00:00'));
        $this->confirmationToken->send($sendAt);

        // Should be allowed immediately (0 minutes)
        $expected = new \DateTimeImmutable('2024-01-01 12:00:00');
        $this->assertEquals($expected, $this->confirmationToken->getAllowedToSendAfter());
    }

    public function testAfterFirstSendTimesOneGetsOneMinute(): void
    {
        $sendAt = new \DateTimeImmutable('2024-01-01 12:00:00');

        // After first send: timesSent = 1, lookup key 1 => 1 minute
        $this->confirmationToken->setTimesSent(1);
        $this->confirmationToken->setAllowedToSendAfter(new \DateTimeImmutable('2024-01-01 11:00:00'));
        $this->confirmationToken->send($sendAt);

        // Should wait 1 minute
        $expected = new \DateTimeImmutable('2024-01-01 12:01:00');
        $this->assertEquals($expected, $this->confirmationToken->getAllowedToSendAfter());
    }

    public function testAfterSecondSendTimesTwoGetsThreeMinutes(): void
    {
        $sendAt = new \DateTimeImmutable('2024-01-01 12:00:00');

        // After second send: timesSent = 2, lookup key 2 => 3 minutes
        $this->confirmationToken->setTimesSent(2);
        $this->confirmationToken->setAllowedToSendAfter(new \DateTimeImmutable('2024-01-01 11:00:00'));
        $this->confirmationToken->send($sendAt);

        // Should wait 3 minutes
        $expected = new \DateTimeImmutable('2024-01-01 12:03:00');
        $this->assertEquals($expected, $this->confirmationToken->getAllowedToSendAfter());
    }

    public function testAfterThirdSendTimesThreeGetsFourMinutes(): void
    {
        $sendAt = new \DateTimeImmutable('2024-01-01 12:00:00');

        // After third send: timesSent = 3, lookup key 3 => 4 minutes
        $this->confirmationToken->setTimesSent(3);
        $this->confirmationToken->setAllowedToSendAfter(new \DateTimeImmutable('2024-01-01 11:00:00'));
        $this->confirmationToken->send($sendAt);

        // Should wait 4 minutes
        $expected = new \DateTimeImmutable('2024-01-01 12:04:00');
        $this->assertEquals($expected, $this->confirmationToken->getAllowedToSendAfter());
    }

    public function testAfterFourthSendTimesFourGets1440Minutes(): void
    {
        $sendAt = new \DateTimeImmutable('2024-01-01 12:00:00');

        // After fourth send: timesSent = 4, lookup key 4 => 1440 minutes
        $this->confirmationToken->setTimesSent(4);
        $this->confirmationToken->setAllowedToSendAfter(new \DateTimeImmutable('2024-01-01 11:00:00'));
        $this->confirmationToken->send($sendAt);

        // Should wait 1440 minutes (24 hours)
        $expected = new \DateTimeImmutable('2024-01-02 12:00:00');
        $this->assertEquals($expected, $this->confirmationToken->getAllowedToSendAfter());
    }

    public function testBeyondConfiguredAttemptsDefaultsToZeroMinutes(): void
    {
        $sendAt = new \DateTimeImmutable('2024-01-01 12:00:00');

        // timesSent = 5, lookup key 5 => not found => defaults to 0
        $this->confirmationToken->setTimesSent(5);
        $this->confirmationToken->setAllowedToSendAfter(new \DateTimeImmutable('2024-01-01 11:00:00'));
        $this->confirmationToken->send($sendAt);

        // Should be allowed immediately
        $expected = new \DateTimeImmutable('2024-01-01 12:00:00');
        $this->assertEquals($expected, $this->confirmationToken->getAllowedToSendAfter());
    }

    public function testSendEmailAttemptsConfigurationConstant(): void
    {
        // Test the class constant to kill all array literal mutants
        $reflection = new \ReflectionClass(ConfirmationToken::class);
        $constant = $reflection->getConstant('SEND_EMAIL_ATTEMPTS_TIME_IN_MINUTES');

        // Verify exact configuration
        $this->assertIsArray($constant);
        $this->assertCount(4, $constant);

        // Verify exact keys and values
        $this->assertArrayHasKey(1, $constant);
        $this->assertArrayHasKey(2, $constant);
        $this->assertArrayHasKey(3, $constant);
        $this->assertArrayHasKey(4, $constant);

        $this->assertSame(1, $constant[1]);
        $this->assertSame(3, $constant[2]);
        $this->assertSame(4, $constant[3]);
        $this->assertSame(1440, $constant[4]);

        // Verify complete array equality
        $this->assertSame([
            1 => 1,
            2 => 3,
            3 => 4,
            4 => 1440,
        ], $constant);
    }
}
