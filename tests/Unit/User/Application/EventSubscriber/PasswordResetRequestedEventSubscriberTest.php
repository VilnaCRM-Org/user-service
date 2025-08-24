<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SendPasswordResetEmailCommand;
use App\User\Application\EventSubscriber\PasswordResetRequestedEventSubscriber;
use App\User\Application\Factory\SendPasswordResetEmailCommandFactoryInterface;
use App\User\Domain\Aggregate\PasswordResetEmailInterface;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordResetRequestedEvent;
use App\User\Domain\Factory\PasswordResetEmailFactoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;

final class PasswordResetRequestedEventSubscriberTest extends UnitTestCase
{
    private CommandBusInterface $commandBus;
    private PasswordResetTokenRepositoryInterface $tokenRepository;
    private PasswordResetEmailFactoryInterface $emailFactory;
    private SendPasswordResetEmailCommandFactoryInterface $cmdFactory;
    private PasswordResetRequestedEventSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->tokenRepository = $this->createMock(PasswordResetTokenRepositoryInterface::class);
        $this->emailFactory = $this->createMock(PasswordResetEmailFactoryInterface::class);
        $this->cmdFactory = $this->createMock(SendPasswordResetEmailCommandFactoryInterface::class);

        $this->subscriber = new PasswordResetRequestedEventSubscriber(
            $this->commandBus,
            $this->tokenRepository,
            $this->emailFactory,
            $this->cmdFactory
        );
    }

    public function testInvokeSuccessfully(): void
    {
        $tokenValue = $this->faker->sha256();
        $eventId = $this->faker->uuid();

        $user = $this->createMock(UserInterface::class);
        $token = $this->createMock(PasswordResetTokenInterface::class);
        $passwordResetEmail = $this->createMock(PasswordResetEmailInterface::class);
        $command = $this->createMock(SendPasswordResetEmailCommand::class);

        $event = new PasswordResetRequestedEvent($user, $tokenValue, $eventId);

        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($tokenValue)
            ->willReturn($token);

        $this->emailFactory->expects($this->once())
            ->method('create')
            ->with($token, $user)
            ->willReturn($passwordResetEmail);

        $this->cmdFactory->expects($this->once())
            ->method('create')
            ->with($passwordResetEmail)
            ->willReturn($command);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);

        $this->subscriber->__invoke($event);
    }

    public function testInvokeWhenTokenNotFound(): void
    {
        $tokenValue = $this->faker->sha256();
        $eventId = $this->faker->uuid();

        $user = $this->createMock(UserInterface::class);
        $event = new PasswordResetRequestedEvent($user, $tokenValue, $eventId);

        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($tokenValue)
            ->willReturn(null);

        $this->emailFactory->expects($this->never())
            ->method('create');

        $this->cmdFactory->expects($this->never())
            ->method('create');

        $this->commandBus->expects($this->never())
            ->method('dispatch');

        $this->subscriber->__invoke($event);
    }

    public function testSubscribedTo(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        $this->assertIsArray($subscribedEvents);
        $this->assertContains(PasswordResetRequestedEvent::class, $subscribedEvents);
        $this->assertCount(1, $subscribedEvents);
    }
}
