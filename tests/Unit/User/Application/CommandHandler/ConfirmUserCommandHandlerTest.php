<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\CommandHandler\ConfirmUserCommandHandler;
use App\User\Application\Factory\ConfirmUserCommandFactory;
use App\User\Application\Factory\ConfirmUserCommandFactoryInterface;
use App\User\Domain\Event\UserConfirmedEvent;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\UserConfirmedEventFactory;
use App\User\Domain\Factory\Event\UserConfirmedEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

class ConfirmUserCommandHandlerTest extends UnitTestCase
{
    private ConfirmUserCommandHandler $handler;
    private UserRepositoryInterface $userRepository;
    private EventBusInterface $eventBus;
    private UuidFactory $mockUuidFactory;
    private UserConfirmedEventFactoryInterface $mockUserConfirmedEventFactory;
    private UuidFactory $uuidFactory;
    private UserConfirmedEventFactoryInterface $userConfirmedEventFactory;
    private ConfirmUserCommandFactoryInterface $confirmUserCommandFactory;
    private UuidTransformer $uuidTransformer;
    private UserFactoryInterface $userFactory;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(
            UserRepositoryInterface::class
        );
        $this->eventBus = $this->createMock(
            EventBusInterface::class
        );
        $this->mockUuidFactory = $this->createMock(
            UuidFactory::class
        );
        $this->mockUserConfirmedEventFactory = $this->createMock(
            UserConfirmedEventFactoryInterface::class
        );
        $this->uuidFactory = new UuidFactory();
        $this->userConfirmedEventFactory = new UserConfirmedEventFactory();
        $this->uuidTransformer = new UuidTransformer();
        $this->userFactory = new UserFactory();
        $this->confirmUserCommandFactory = new ConfirmUserCommandFactory();
        $this->confirmationTokenFactory = new ConfirmationTokenFactory($this->faker->numberBetween(1, 10));

        $this->handler = new ConfirmUserCommandHandler(
            $this->userRepository,
            $this->eventBus,
            $this->mockUuidFactory,
            $this->mockUserConfirmedEventFactory
        );
    }

    public function testInvoke(): void
    {
        $email = $this->faker->email();
        $name = $this->faker->name();
        $password = $this->faker->password();
        $userId = $this->uuidTransformer->transformFromString($this->faker->uuid());

        $user = $this->userFactory->create($email, $name, $password, $userId);
        $token = $this->confirmationTokenFactory->create($user->getId());

        $this->userRepository->expects($this->once())
            ->method('find')
            ->willReturn($user);

        $this->userRepository->expects($this->once())
            ->method('save');

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(UserConfirmedEvent::class));

        $this->mockUuidFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->uuidFactory->create());

        $this->mockUserConfirmedEventFactory->expects($this->once())
            ->method('create')
            ->willReturn(
                $this->userConfirmedEventFactory->create(
                    $token,
                    $this->faker->uuid()
                )
            );

        $command = $this->confirmUserCommandFactory->create($token);
        $this->handler->__invoke($command);

        $this->assertTrue($user->isConfirmed());
    }

    public function testInvokeUserNotFound(): void
    {
        $this->userRepository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);

        $token = $this->confirmationTokenFactory->create(
            $this->faker->uuid(),
        );
        $command = $this->confirmUserCommandFactory->create($token);
        $this->handler->__invoke($command);
    }
}
