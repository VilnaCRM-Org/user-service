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
}
