<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;

final class ConfirmationTokenSendTimingTest extends UnitTestCase
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

    public function testSendSetsAllowedToSendAfterInFuture(): void
    {
        $beforeSend = new \DateTimeImmutable();
        $this->confirmationToken->send();

        $allowedToSendAfter = $this->confirmationToken->getAllowedToSendAfter();

        $this->assertGreaterThan(
            $beforeSend,
            $allowedToSendAfter
        );
        $this->assertGreaterThanOrEqual(
            $beforeSend->modify('+1 minute'),
            $allowedToSendAfter
        );
    }

    public function testSendBeyondDefinedRateLimitsUsesNoDelay(): void
    {
        $this->confirmationToken->setTimesSent(10);
        $beforeSend = new \DateTimeImmutable();
        $this->confirmationToken->setAllowedToSendAfter(
            new \DateTimeImmutable('1970-01-01')
        );

        $this->confirmationToken->send($beforeSend);

        $this->assertEquals(
            $beforeSend,
            $this->confirmationToken->getAllowedToSendAfter()
        );
    }

    public function testFirstSendWithTimesZeroDefaultsToOneMinute(): void
    {
        $sendAt = new \DateTimeImmutable('2024-01-01 12:00:00');

        $this->confirmationToken->setTimesSent(0);
        $this->confirmationToken->setAllowedToSendAfter(new \DateTimeImmutable(
            '2024-01-01 11:00:00'
        ));
        $this->confirmationToken->send($sendAt);

        $expected = new \DateTimeImmutable('2024-01-01 12:01:00');
        $this->assertEquals($expected, $this->confirmationToken->getAllowedToSendAfter());
    }

    public function testAfterFirstSendTimesOneGetsOneMinute(): void
    {
        $sendAt = new \DateTimeImmutable('2024-01-01 12:00:00');

        $this->confirmationToken->setTimesSent(1);
        $this->confirmationToken->setAllowedToSendAfter(new \DateTimeImmutable(
            '2024-01-01 11:00:00'
        ));
        $this->confirmationToken->send($sendAt);

        $expected = new \DateTimeImmutable('2024-01-01 12:01:00');
        $this->assertEquals($expected, $this->confirmationToken->getAllowedToSendAfter());
    }

    public function testAfterSecondSendTimesTwoGetsThreeMinutes(): void
    {
        $sendAt = new \DateTimeImmutable('2024-01-01 12:00:00');

        $this->confirmationToken->setTimesSent(2);
        $this->confirmationToken->setAllowedToSendAfter(new \DateTimeImmutable(
            '2024-01-01 11:00:00'
        ));
        $this->confirmationToken->send($sendAt);

        $expected = new \DateTimeImmutable('2024-01-01 12:03:00');
        $this->assertEquals($expected, $this->confirmationToken->getAllowedToSendAfter());
    }

    public function testAfterThirdSendTimesThreeGetsFourMinutes(): void
    {
        $sendAt = new \DateTimeImmutable('2024-01-01 12:00:00');

        $this->confirmationToken->setTimesSent(3);
        $this->confirmationToken->setAllowedToSendAfter(new \DateTimeImmutable(
            '2024-01-01 11:00:00'
        ));
        $this->confirmationToken->send($sendAt);

        $expected = new \DateTimeImmutable('2024-01-01 12:04:00');
        $this->assertEquals($expected, $this->confirmationToken->getAllowedToSendAfter());
    }

    public function testAfterFourthSendTimesFourGets1440Minutes(): void
    {
        $sendAt = new \DateTimeImmutable('2024-01-01 12:00:00');

        $this->confirmationToken->setTimesSent(4);
        $this->confirmationToken->setAllowedToSendAfter(new \DateTimeImmutable(
            '2024-01-01 11:00:00'
        ));
        $this->confirmationToken->send($sendAt);

        $expected = new \DateTimeImmutable('2024-01-02 12:00:00');
        $this->assertEquals($expected, $this->confirmationToken->getAllowedToSendAfter());
    }

    public function testBeyondConfiguredAttemptsDefaultsToZeroMinutes(): void
    {
        $sendAt = new \DateTimeImmutable('2024-01-01 12:00:00');

        $this->confirmationToken->setTimesSent(5);
        $this->confirmationToken->setAllowedToSendAfter(
            new \DateTimeImmutable(
                '2024-01-01 11:00:00'
            )
        );
        $this->confirmationToken->send($sendAt);

        $expected = new \DateTimeImmutable('2024-01-01 12:00:00');
        $this->assertEquals($expected, $this->confirmationToken->getAllowedToSendAfter());
    }
}
