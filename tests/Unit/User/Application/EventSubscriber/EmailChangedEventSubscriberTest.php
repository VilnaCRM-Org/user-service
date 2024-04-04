<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\EmailChangedEventSubscriber;
use App\User\Application\Factory\SendConfirmationEmailCommandFactory;
use App\User\Application\Factory\SendConfirmationEmailCommandFactoryInterface;
use App\User\Domain\Event\EmailChangedEvent;
use App\User\Domain\Factory\ConfirmationEmailFactory;
use App\User\Domain\Factory\ConfirmationEmailFactoryInterface;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactory;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactoryInterface;
use App\User\Domain\Factory\Event\EmailChangedEventFactory;
use App\User\Domain\Factory\Event\EmailChangedEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;

final class EmailChangedEventSubscriberTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    private UuidTransformer $uuidTransformer;
    private EmailChangedEventFactoryInterface $emailChangedEventFactory;
    private ConfirmationEmailFactoryInterface $confirmationEmailFactory;
    private ConfirmationEmailSendEventFactoryInterface $sendEventFactory;
    private SendConfirmationEmailCommandFactoryInterface $commandFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->confirmationTokenFactory = new ConfirmationTokenFactory($this->faker->numberBetween(1, 10));
        $this->uuidTransformer = new UuidTransformer();
        $this->emailChangedEventFactory = new EmailChangedEventFactory();
        $this->sendEventFactory = new ConfirmationEmailSendEventFactory();
        $this->confirmationEmailFactory = new ConfirmationEmailFactory($this->sendEventFactory);
        $this->commandFactory = new SendConfirmationEmailCommandFactory();
    }

    public function testInvoke(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $tokenFactory = $this->createMock(ConfirmationTokenFactoryInterface::class);
        $mockConfirmationEmailFactory = $this->createMock(ConfirmationEmailFactoryInterface::class);
        $emailCmdFactory = $this->createMock(SendConfirmationEmailCommandFactoryInterface::class);

        $subscriber = new EmailChangedEventSubscriber(
            $commandBus,
            $tokenFactory,
            $mockConfirmationEmailFactory,
            $emailCmdFactory
        );

        $emailAddress = $this->faker->email();
        $user = $this->userFactory->create(
            $emailAddress,
            $this->faker->name(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );

        $event = $this->emailChangedEventFactory->create($user, $this->faker->uuid());

        $token = $this->confirmationTokenFactory->create($this->faker->uuid());
        $tokenFactory->expects($this->once())
            ->method('create')
            ->with($this->equalTo($user->getId()))
            ->willReturn($token);

        $confirmationEmail = $this->confirmationEmailFactory->create($token, $user);
        $mockConfirmationEmailFactory->expects($this->once())
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
            [EmailChangedEvent::class],
            EmailChangedEventSubscriber::subscribedTo()
        );
    }
}
