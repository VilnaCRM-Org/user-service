<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\DeleteUserCommand;
use App\User\Application\CommandHandler\DeleteUserCommandHandler;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\UserDeletedEvent;
use App\User\Domain\Factory\Event\UserDeletedEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Uuid;

final class DeleteUserCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private EventBusInterface&MockObject $eventBus;
    private UuidFactory&MockObject $uuidFactory;
    private UserDeletedEventFactoryInterface&MockObject $eventFactory;
    private DeleteUserCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactory::class);
        $this->eventFactory = $this->createMock(UserDeletedEventFactoryInterface::class);
        $this->handler = new DeleteUserCommandHandler(
            $this->userRepository,
            $this->eventBus,
            $this->uuidFactory,
            $this->eventFactory
        );
    }

    public function testInvokeDeletesUserAndPublishesEvent(): void
    {
        $user = $this->createMock(UserInterface::class);
        $command = new DeleteUserCommand($user);

        $eventId = Uuid::v4();
        $event = $this->createMock(UserDeletedEvent::class);

        $this->userRepository->expects($this->once())
            ->method('delete')
            ->with($user);

        $this->uuidFactory->expects($this->once())
            ->method('create')
            ->willReturn($eventId);

        $this->eventFactory->expects($this->once())
            ->method('create')
            ->with($user, (string) $eventId)
            ->willReturn($event);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($event);

        $this->handler->__invoke($command);
    }
}
