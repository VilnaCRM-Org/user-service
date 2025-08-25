<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SendPasswordResetEmailCommand;
use App\User\Application\CommandHandler\SendPasswordResetEmailCommandHandler;
use App\User\Domain\Aggregate\PasswordResetEmail;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordResetEmailSentEvent;
use App\User\Domain\Factory\Event\PasswordResetEmailSendEventFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Uuid;

final class SendPasswordResetEmailCommandHandlerTest extends UnitTestCase
{
    private EventBusInterface $eventBus;
    private UuidFactory $uuidFactory;
    private SendPasswordResetEmailCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactory::class);

        $this->handler = new SendPasswordResetEmailCommandHandler(
            $this->eventBus,
            $this->uuidFactory
        );
    }

    public function testInvokeSuccessfully(): void
    {
        $eventId = Uuid::v4();
        
        // Create concrete dependencies for PasswordResetEmail
        $token = $this->createMock(PasswordResetTokenInterface::class);
        $user = $this->createMock(UserInterface::class);
        $factory = $this->createMock(PasswordResetEmailSendEventFactoryInterface::class);
        
        $passwordResetEmailSentEvent = $this->createMock(PasswordResetEmailSentEvent::class);
        
        $factory->expects($this->once())
            ->method('create')
            ->with($token, $user, (string) $eventId)
            ->willReturn($passwordResetEmailSentEvent);

        // Create concrete PasswordResetEmail
        $passwordResetEmail = new PasswordResetEmail($token, $user, $factory);

        $command = new SendPasswordResetEmailCommand($passwordResetEmail);

        $this->uuidFactory->expects($this->once())
            ->method('create')
            ->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($passwordResetEmailSentEvent);

        $this->handler->__invoke($command);
    }
}