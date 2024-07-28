<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Builders\ConfirmationTokenBuilder;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\CommandHandler\ConfirmPasswordResetCommandHandler;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordChangedEvent;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final class ConfirmPasswordResetCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface $userRepositoryMock;
    private UserInterface $userMock;
    private UuidFactory $uuidFactory;
    private PasswordChangedEventFactoryInterface $passwordChangedEventFactoryStub;
    private PasswordHasherFactoryInterface $passwordHasherFactoryStub;
    private PasswordHasherInterface $passwordHasherMock;
    private EventBusInterface $eventBusMock;
    private PasswordChangedEvent $passwordChangedEventStub;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uuidFactory = new UuidFactory();
        $this->passwordChangedEventFactoryStub = $this->createStub(PasswordChangedEventFactoryInterface::class);
        $this->passwordHasherFactoryStub = $this->createStub(PasswordHasherFactoryInterface::class);
        $this->passwordChangedEventStub = $this->createStub(PasswordChangedEvent::class);

        $this->userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->userMock = $this->createMock(UserInterface::class);
        $this->passwordHasherMock = $this->createMock(PasswordHasherInterface::class);
        $this->eventBusMock = $this->createMock(EventBusInterface::class);
    }

    public function testInvoke(): void
    {
        $this->userRepositoryMock->method('find')
            ->willReturn($this->userMock);
        $this->passwordHasherFactoryStub->method('getPasswordHasher')
            ->willReturn($this->passwordHasherMock);

        $this->userMock->expects($this->once())->method('updatePassword')
            ->willReturn([$this->passwordChangedEventStub]);
        $this->passwordHasherMock->expects($this->once())->method('hash');
        $this->userRepositoryMock->expects($this->once())->method('save');
        $this->eventBusMock->expects($this->once())
            ->method('publish')
            ->with($this->passwordChangedEventStub);

        $command = new ConfirmPasswordResetCommand(
            (new ConfirmationTokenBuilder())->build(),
            $this->faker->password()
        );
        $commandHandler = $this->getCommandHandler();
        $commandHandler->__invoke($command);
    }

    public function testCanHandleWithNotExistingUser(): void
    {
        $this->userRepositoryMock->method('find')
            ->willReturn(null);
        $this->passwordHasherFactoryStub->method('getPasswordHasher')
            ->willReturn($this->passwordHasherMock);

        $this->expectException(UserNotFoundException::class);
        $this->userMock->expects($this->never())->method('updatePassword')
            ->willReturn([$this->passwordChangedEventStub]);
        $this->passwordHasherMock->expects($this->never())->method('hash');
        $this->userRepositoryMock->expects($this->never())->method('save');
        $this->eventBusMock->expects($this->never())
            ->method('publish')
            ->with($this->passwordChangedEventStub);

        $command = new ConfirmPasswordResetCommand(
            (new ConfirmationTokenBuilder())->build(),
            $this->faker->password()
        );
        $commandHandler = $this->getCommandHandler();
        $commandHandler->__invoke($command);
    }

    private function getCommandHandler(): ConfirmPasswordResetCommandHandler
    {
        return new ConfirmPasswordResetCommandHandler(
            $this->userRepositoryMock,
            $this->passwordHasherFactoryStub,
            $this->uuidFactory,
            $this->passwordChangedEventFactoryStub,
            $this->eventBusMock
        );
    }
}
