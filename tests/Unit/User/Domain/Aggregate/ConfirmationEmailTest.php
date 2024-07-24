<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Aggregate;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Aggregate\ConfirmationEmail;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactoryInterface;
use App\User\Domain\Factory\Event\PasswordResetRequestedEventFactoryInterface;

final class ConfirmationEmailTest extends UnitTestCase
{
    public function testSend(): void
    {
        $token =
            $this->createMock(ConfirmationTokenInterface::class);
        $token->expects($this->once())
            ->method('send');

        $user = $this->createMock(UserInterface::class);

        $eventFactory = $this->createMock(
            ConfirmationEmailSendEventFactoryInterface::class
        );
        $eventFactory->expects($this->once())
            ->method('create');

        $confirmationEmail =
            new ConfirmationEmail($token, $user);

        $confirmationEmail->send($this->faker->uuid(), $eventFactory);
    }

    public function testSendPasswordReset(): void
    {
        $token =
            $this->createMock(ConfirmationTokenInterface::class);
        $token->expects($this->once())
            ->method('send');

        $user = $this->createMock(UserInterface::class);

        $eventFactory = $this->createMock(
            PasswordResetRequestedEventFactoryInterface::class
        );
        $eventFactory->expects($this->once())
            ->method('create');

        $confirmationEmail =
            new ConfirmationEmail($token, $user);

        $confirmationEmail->sendPasswordReset($this->faker->uuid(), $eventFactory);
    }
}
