<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\MessageHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Message\UserRegisteredMessage;
use App\User\Application\MessageHandler\UserRegisteredBatchMessageHandler;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\UserRegisteredEvent;
use App\User\Domain\Factory\Event\UserRegisteredEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Uid\Factory\UuidFactory;

final class UserRegisteredBatchMessageHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface $userRepository;
    private EventBusInterface $eventBus;
    private UserRegisteredEventFactoryInterface $registeredEventFactory;
    private UuidFactory $mockUuidFactory;
    private UuidFactory $uuidFactory;
    private UserRegisteredBatchMessageHandler $handler;
    private Acknowledger $ack;

    protected function setUp(): void
    {
        $this->userRepository =
            $this->createMock(UserRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->registeredEventFactory =
            $this->createMock(UserRegisteredEventFactoryInterface::class);
        $this->mockUuidFactory = $this->createMock(UuidFactory::class);
        $this->uuidFactory = new UuidFactory();
        $this->ack = $this->createMock(Acknowledger::class);

        $this->handler = new UserRegisteredBatchMessageHandler(
            $this->userRepository,
            $this->eventBus,
            $this->registeredEventFactory,
            $this->mockUuidFactory
        );
    }

    public function testProcess(): void
    {
        $user = $this->createMock(UserInterface::class);
        $message = new UserRegisteredMessage($user);

        $jobs = [[$message, $this->ack]];

        $this->testProcessSetExpectations($user);

        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('process');
        $method->invokeArgs($this->handler, [$jobs]);
    }

    public function testProcessFailure(): void
    {
        $user = $this->createMock(UserInterface::class);
        $message = new UserRegisteredMessage($user);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user)
            ->will($this->throwException(new \Exception()));

        $this->ack->expects($this->once())
            ->method('nack')
            ->with($this->isInstanceOf(\Exception::class));

        $jobs = [[$message, $this->ack]];

        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('process');
        $method->invokeArgs($this->handler, [$jobs]);
    }

    public function testInvoke(): void
    {
        $user = $this->createMock(UserInterface::class);
        $message = new UserRegisteredMessage($user);

        $handlerMock = $this->getMockBuilder(
            UserRegisteredBatchMessageHandler::class
        )
            ->disableOriginalConstructor()
            ->onlyMethods(['handle'])
            ->getMock();

        $result = $handlerMock->__invoke($message, $this->ack);

        $this->assertSame(1, $result);
    }

    private function testProcessSetExpectations(
        UserInterface $user
    ): void {
        $uuid = $this->uuidFactory->create();
        $event = $this->createMock(UserRegisteredEvent::class);

        $this->mockUuidFactory->expects($this->once())
            ->method('create')
            ->willReturn($uuid);

        $this->registeredEventFactory->expects($this->once())
            ->method('create')
            ->with($user, $uuid)
            ->willReturn($event);

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($event);

        $this->ack->expects($this->once())
            ->method('ack')
            ->with($user);
    }
}
