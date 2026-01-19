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
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Uuid;

final class DeleteUserCommandHandlerTest extends UnitTestCase
{
    public function testInvokeDeletesUserAndPublishesEvent(): void
    {
        $user = $this->createMock(UserInterface::class);
        $command = new DeleteUserCommand($user);

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $eventBus = $this->createMock(EventBusInterface::class);
        $uuidFactory = $this->createMock(UuidFactory::class);
        $eventFactory = $this->createMock(UserDeletedEventFactoryInterface::class);

        $eventId = Uuid::v4();
        $event = $this->createMock(UserDeletedEvent::class);

        $userRepository->expects($this->once())
            ->method('delete')
            ->with($user);

        $uuidFactory->expects($this->once())
            ->method('create')
            ->willReturn($eventId);

        $eventFactory->expects($this->once())
            ->method('create')
            ->with($user, (string) $eventId)
            ->willReturn($event);

        $eventBus->expects($this->once())
            ->method('publish')
            ->with($event);

        $handler = new DeleteUserCommandHandler(
            $userRepository,
            $eventBus,
            $uuidFactory,
            $eventFactory
        );

        $handler->__invoke($command);
    }
}
