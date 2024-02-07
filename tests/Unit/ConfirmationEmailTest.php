<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\User\Domain\Aggregate\ConfirmationEmail;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactoryInterface;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;

class ConfirmationEmailTest extends TestCase
{
    private Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();
    }

    public function testSend(): void
    {
        $token = $this->createMock(ConfirmationTokenInterface::class);
        $token->expects($this->once())
            ->method('send');

        $user = $this->createMock(UserInterface::class);

        $eventFactory = $this->createMock(ConfirmationEmailSendEventFactoryInterface::class);
        $eventFactory->expects($this->once())
            ->method('create');

        $confirmationEmail = new ConfirmationEmail($token, $user, $eventFactory);

        $confirmationEmail->send($this->faker->uuid());
    }
}
