<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\UserRegisteredEventSubscriber;
use App\User\Application\Factory\SendConfirmationEmailCommandFactory;
use App\User\Application\Factory\SendConfirmationEmailCommandFactoryInterface;
use App\User\Domain\Event\UserRegisteredEvent;
use App\User\Domain\Factory\ConfirmationEmailFactory;
use App\User\Domain\Factory\ConfirmationEmailFactoryInterface;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactory;
use App\User\Domain\Factory\Event\UserRegisteredEventFactory;
use App\User\Domain\Factory\Event\UserRegisteredEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;

class UserRegisteredEventSubscriberTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    private UuidTransformer $uuidTransformer;
    private UserRegisteredEventFactoryInterface $userRegisteredEventFactory;
    private ConfirmationEmailFactoryInterface $confirmationEmailFactory;
    private SendConfirmationEmailCommandFactoryInterface $commandFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->confirmationTokenFactory = new ConfirmationTokenFactory(10);
        $this->uuidTransformer = new UuidTransformer();
        $this->userRegisteredEventFactory = new UserRegisteredEventFactory();
        $this->confirmationEmailFactory = new ConfirmationEmailFactory(
            new ConfirmationEmailSendEventFactory()
        );
        $this->commandFactory = new SendConfirmationEmailCommandFactory();
    }

    public function testInvoke(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $tokenFactory = $this->createMock(ConfirmationTokenFactoryInterface::class);
        $confirmationEmailFactory = $this->createMock(ConfirmationEmailFactoryInterface::class);
        $emailCmdFactory = $this->createMock(SendConfirmationEmailCommandFactoryInterface::class);

        $subscriber = new UserRegisteredEventSubscriber(
            $commandBus,
            $tokenFactory,
            $confirmationEmailFactory,
            $emailCmdFactory
        );

        $emailAddress = $this->faker->email();
        $user = $this->userFactory->create(
            $emailAddress,
            $this->faker->name(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
        $token = $this->confirmationTokenFactory->create($user->getId());

        $event = $this->userRegisteredEventFactory->create($user, $this->faker->uuid());

        $tokenFactory->expects($this->once())
            ->method('create')
            ->with($this->equalTo($user->getId()))
            ->willReturn($token);

        $confirmationEmail = $this->confirmationEmailFactory->create($token, $user);
        $confirmationEmailFactory->expects($this->once())
            ->method('create')
            ->with($this->equalTo($token), $this->equalTo($user))
            ->willReturn($confirmationEmail);

        $sendConfirmationEmailCommand = $this->commandFactory->create($confirmationEmail);
        $emailCmdFactory->expects($this->once())
            ->method('create')
            ->with($this->equalTo($confirmationEmail))
            ->willReturn($sendConfirmationEmailCommand);

        $commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($sendConfirmationEmailCommand));

        $subscriber->__invoke($event);
    }

    public function testSubscribedTo(): void
    {
        $this->assertSame(
            [UserRegisteredEvent::class],
            UserRegisteredEventSubscriber::subscribedTo()
        );
    }
}
