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

    public function testSendIncrementsTimesSent(): void
    {
        $this->assertEquals(0, $this->confirmationToken->getTimesSent());
        $this->confirmationToken->send();
        $this->assertEquals(1, $this->confirmationToken->getTimesSent());
    }

    public function testSendSetsAllowedToSendAfterInFuture(): void
    {
        $beforeSend = new \DateTimeImmutable();
        $this->confirmationToken->send();

        $allowedToSendAfter = $this->confirmationToken->getAllowedToSendAfter();

        $this->assertGreaterThan(
            $beforeSend,
            $allowedToSendAfter,
            'allowedToSendAfter should be in the future after first send'
        );
        $this->assertGreaterThanOrEqual(
            $beforeSend->modify('+1 minute'),
            $allowedToSendAfter,
            'allowedToSendAfter should be at least 1 minute after send time'
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
            $this->confirmationToken->getAllowedToSendAfter(),
            'When beyond defined rate limits, no delay should be added'
        );
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
}
