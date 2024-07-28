<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Builders\UserBuilder;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\CommandHandler\RequestPasswordResetCommandHandler;
use App\User\Application\Factory\RequestPasswordResetCommandFactory;
use App\User\Domain\Aggregate\ConfirmationEmail;
use App\User\Domain\Aggregate\ConfirmationEmailInterface;
use App\User\Domain\Event\PasswordResetRequestedEvent;
use App\User\Domain\Exception\UserByEmailNotFoundException;
use App\User\Domain\Exception\UserIsNotConfirmedException;
use App\User\Domain\Factory\ConfirmationEmailFactoryInterface;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\PasswordResetRequestedEventFactory;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final class RequestPasswordResetCommandHandlerTest extends UnitTestCase
{
    private UuidFactory $uuidFactory;

    private PasswordResetRequestedEventFactory $passwordResetRequestedEventFactoryStub;
    private PasswordResetRequestedEvent $passwordResetRequestedEventStub;
    private UserRepositoryInterface $userRepositoryStub;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactoryStub;
    private ConfirmationEmailFactoryInterface $confirmationEmailFactoryStub;

    private EventBusInterface $eventBusMock;
    private ConfirmationEmailInterface $confirmationEmailMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uuidFactory = new UuidFactory();
        $this->passwordResetRequestedEventFactoryStub = $this->createStub(PasswordResetRequestedEventFactory::class);
        $this->passwordResetRequestedEventStub = $this->createStub(PasswordResetRequestedEvent::class);
        $this->confirmationEmailFactoryStub = $this->createStub(ConfirmationEmailFactoryInterface::class);
        $this->confirmationTokenFactoryStub = $this->createStub(ConfirmationTokenFactoryInterface::class);
        $this->userRepositoryStub = $this->createStub(UserRepositoryInterface::class);

        $this->eventBusMock = $this->createMock(EventBusInterface::class);
        $this->confirmationEmailMock = $this->createMock(ConfirmationEmail::class);
    }

    public function testInvoke(): void
    {
        $user = (new UserBuilder())->confirmed()->build();
        $this->userRepositoryStub->method('findByEmail')
            ->willReturn($user);
        $this->confirmationEmailFactoryStub->method('create')
            ->willReturn($this->confirmationEmailMock);

        $this->confirmationEmailMock->expects($this->once())->method('sendPasswordReset');
        $this->confirmationEmailMock->expects($this->once())->method('pullDomainEvents')
            ->willReturn([$this->passwordResetRequestedEventStub]);
        $this->eventBusMock->expects($this->once())->method('publish')
            ->with($this->passwordResetRequestedEventStub);

        $command = (new RequestPasswordResetCommandFactory())->create($user->getEmail());
        $commandHandler = $this->getCommandHandler();
        $commandHandler->__invoke($command);
    }

    public function testCanHandleWithNotExistingEmail(): void
    {
        $user = (new UserBuilder())->confirmed()->build();
        $this->userRepositoryStub->method('findByEmail')
            ->willReturn(null);
        $this->confirmationEmailFactoryStub->method('create')
            ->willReturn($this->confirmationEmailMock);

        $this->expectException(UserByEmailNotFoundException::class);
        $this->confirmationEmailMock->expects($this->never())->method('sendPasswordReset');
        $this->confirmationEmailMock->expects($this->never())->method('pullDomainEvents')
            ->willReturn([$this->passwordResetRequestedEventStub]);
        $this->eventBusMock->expects($this->never())->method('publish')
            ->with($this->passwordResetRequestedEventStub);

        $command = (new RequestPasswordResetCommandFactory())->create($user->getEmail());
        $commandHandler = $this->getCommandHandler();
        $commandHandler->__invoke($command);
    }

    public function testCanHandleWithUserNotConfirmed(): void
    {
        $user = (new UserBuilder())->build();
        $this->userRepositoryStub->method('findByEmail')
            ->willReturn($user);
        $this->confirmationEmailFactoryStub->method('create')
            ->willReturn($this->confirmationEmailMock);

        $this->expectException(UserIsNotConfirmedException::class);
        $this->confirmationEmailMock->expects($this->never())->method('sendPasswordReset');
        $this->confirmationEmailMock->expects($this->never())->method('pullDomainEvents')
            ->willReturn([$this->passwordResetRequestedEventStub]);
        $this->eventBusMock->expects($this->never())->method('publish')
            ->with($this->passwordResetRequestedEventStub);

        $command = (new RequestPasswordResetCommandFactory())->create($user->getEmail());
        $commandHandler = $this->getCommandHandler();
        $commandHandler->__invoke($command);
    }

    private function getCommandHandler(): RequestPasswordResetCommandHandler
    {
        return new RequestPasswordResetCommandHandler(
            $this->userRepositoryStub,
            $this->eventBusMock,
            $this->passwordResetRequestedEventFactoryStub,
            $this->uuidFactory,
            $this->confirmationEmailFactoryStub,
            $this->confirmationTokenFactoryStub
        );
    }
}
