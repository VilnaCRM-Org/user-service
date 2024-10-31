<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\UserRegisteredEventSubscriber;
use App\User\Application\Factory\SendConfirmationEmailCommandFactory;
use App\User\Application\Factory\SendConfirmationEmailCommandFactoryInterface;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\UserRegisteredEvent;
use App\User\Domain\Factory\ConfirmationEmailFactory;
use App\User\Domain\Factory\ConfirmationEmailFactoryInterface;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\UserRegisteredEventFactory;
use App\User\Domain\Factory\Event\UserRegisteredEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;

final class UserRegisteredEventSubscriberTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    private UuidTransformer $uuidTransformer;
    private UserRegisteredEventFactoryInterface $userRegisteredEventFactory;
    private ConfirmationEmailFactoryInterface $confirmationEmailFactory;
    private SendConfirmationEmailCommandFactoryInterface $commandFactory;
    private CommandBusInterface $commandBus;
    private ConfirmationTokenFactoryInterface $tokenFactory;
    private ConfirmationEmailFactoryInterface $mockConfirmationEmailFactory;
    private SendConfirmationEmailCommandFactoryInterface $emailCmdFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->confirmationTokenFactory = new ConfirmationTokenFactory(
            $this->faker->numberBetween(1, 10)
        );
        $this->uuidTransformer = new UuidTransformer();
        $this->userRegisteredEventFactory = new UserRegisteredEventFactory();
        $this->confirmationEmailFactory = new ConfirmationEmailFactory();
        $this->commandFactory = new SendConfirmationEmailCommandFactory();
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->tokenFactory =
            $this->createMock(ConfirmationTokenFactoryInterface::class);
        $this->mockConfirmationEmailFactory =
            $this->createMock(ConfirmationEmailFactoryInterface::class);
        $this->emailCmdFactory = $this->createMock(
            SendConfirmationEmailCommandFactoryInterface::class
        );
    }

    public function testInvoke(): void
    {
        $emailAddress = $this->faker->email();
        $user = $this->userFactory->create(
            $emailAddress,
            $this->faker->name(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
        $token = $this->confirmationTokenFactory->create($user->getId());
        $event = $this->userRegisteredEventFactory->create(
            $user,
            $this->faker->uuid()
        );

        $this->testInvokeSetExpectations($user, $token);

        $this->getSubscriber()->__invoke($event);
    }

    public function testSubscribedTo(): void
    {
        $this->assertSame(
            [UserRegisteredEvent::class],
            $this->getSubscriber()->subscribedTo()
        );
    }

    private function getSubscriber(): UserRegisteredEventSubscriber
    {
        return new UserRegisteredEventSubscriber(
            $this->commandBus,
            $this->tokenFactory,
            $this->mockConfirmationEmailFactory,
            $this->emailCmdFactory
        );
    }

    private function testInvokeSetExpectations(
        UserInterface $user,
        ConfirmationTokenInterface $token
    ): void {
        $confirmationEmail =
            $this->confirmationEmailFactory->create($token, $user);
        $sendConfirmationEmailCommand =
            $this->commandFactory->create($confirmationEmail);

        $this->tokenFactory->expects($this->once())
            ->method('create')
            ->with($this->equalTo($user->getId()))
            ->willReturn($token);

        $this->mockConfirmationEmailFactory->expects($this->once())
            ->method('create')
            ->with($this->equalTo($token), $this->equalTo($user))
            ->willReturn($confirmationEmail);

        $this->emailCmdFactory->expects($this->once())
            ->method('create')
            ->with($this->equalTo($confirmationEmail))
            ->willReturn($sendConfirmationEmailCommand);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->equalTo($sendConfirmationEmailCommand));
    }
}
