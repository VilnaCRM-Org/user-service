<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\CommandHandler\RequestPasswordResetCommandHandler;
use App\User\Application\Factory\RequestPasswordResetCommandFactory;
use App\User\Domain\Aggregate\ConfirmationEmail;
use App\User\Domain\Aggregate\ConfirmationEmailInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\UserByEmailNotFoundException;
use App\User\Domain\Exception\UserIsNotConfirmedException;
use App\User\Domain\Factory\ConfirmationEmailFactoryInterface;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\PasswordResetRequestedEventFactory;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final class RequestPasswordResetCommandHandlerTest extends UnitTestCase
{
    private EventBusInterface $eventBus;
    private UserRepositoryInterface $userRepository;
    private PasswordResetRequestedEventFactory $passwordResetRequestedEventFactory;
    private UuidFactory $uuidFactory;
    private UserFactoryInterface $userFactory;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    private ConfirmationEmailFactoryInterface $confirmationEmailFactory;
    private ConfirmationEmailInterface $confirmationEmail;
    private UuidTransformer $uuidTransformer;
    private RequestPasswordResetCommandFactory $commandFactory;
    private RequestPasswordResetCommandHandler $commandHandler;
    private UserInterface $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initializeMocks();
        $this->initializeFactories();
        $this->initializeCommandHandler();
        $this->setUser();
    }

    public function testInvoke(): void
    {
        $this->user->setConfirmed(true);

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->willReturn($this->user);

        $command = $this->commandFactory->create($this->user->getEmail());

        $this->confirmationEmail->expects($this->once())
            ->method('pullDomainEvents');
        $this->confirmationEmailFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->confirmationEmail);

        $this->eventBus->expects($this->once())
            ->method('publish');

        $this->commandHandler->__invoke($command);
        $this->assertTrue(true);
    }

    public function testCanHandleWithNotExistingEmail(): void
    {
        $this->user->setConfirmed(true);

        $this->expectException(UserByEmailNotFoundException::class);

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->willReturn(null);

        $command = $this->commandFactory->create($this->user->getEmail());

        $this->confirmationEmail->expects($this->never())
            ->method('pullDomainEvents');
        $this->confirmationEmailFactory->expects($this->never())
            ->method('create')
            ->willReturn($this->confirmationEmail);

        $this->eventBus->expects($this->never())
            ->method('publish');

        $this->commandHandler->__invoke($command);
    }

    public function testCanHandleUserNotConfirmed(): void
    {
        $this->user->setConfirmed(false);

        $this->expectException(UserIsNotConfirmedException::class);

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->willReturn($this->user);

        $command = $this->commandFactory->create($this->user->getEmail());

        $this->confirmationEmail->expects($this->never())
            ->method('pullDomainEvents');
        $this->confirmationEmailFactory->expects($this->never())
            ->method('create')
            ->willReturn($this->confirmationEmail);

        $this->eventBus->expects($this->never())
            ->method('publish');

        $this->commandHandler->__invoke($command);
    }

    private function setUser(): void
    {
        $email = $this->faker->email();
        $name = $this->faker->name();
        $password = $this->faker->password();
        $userId = $this->uuidTransformer->transformFromString(
            $this->faker->uuid()
        );
        $this->user = $this->userFactory->create($email, $name, $password, $userId);
    }

    private function initializeMocks(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->confirmationEmailFactory = $this->createMock(ConfirmationEmailFactoryInterface::class);
        $this->confirmationEmail = $this->createMock(ConfirmationEmail::class);
        $this->confirmationTokenFactory = $this->createMock(ConfirmationTokenFactoryInterface::class);
    }

    private function initializeFactories(): void
    {
        $this->passwordResetRequestedEventFactory = new PasswordResetRequestedEventFactory();
        $this->uuidFactory = new UuidFactory();
        $this->commandFactory = new RequestPasswordResetCommandFactory();
        $this->uuidTransformer = new UuidTransformer();
        $this->userFactory = new UserFactory();
    }

    private function initializeCommandHandler(): void
    {
        $this->commandHandler = new RequestPasswordResetCommandHandler(
            $this->userRepository,
            $this->eventBus,
            $this->passwordResetRequestedEventFactory,
            $this->uuidFactory,
            $this->confirmationEmailFactory,
            $this->confirmationTokenFactory
        );
    }
}
