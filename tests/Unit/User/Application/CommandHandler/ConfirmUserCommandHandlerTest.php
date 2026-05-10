<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as UuidFactoryInterface;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\CommandHandler\ConfirmUserCommandHandler;
use App\User\Application\Factory\ConfirmUserCommandFactory;
use App\User\Application\Factory\ConfirmUserCommandFactoryInterface;
use App\User\Application\Query\GetUserQueryHandler;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\UserConfirmedEvent;
use App\User\Domain\Event\UserUpdatedEvent;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\UserConfirmedEventFactory;
use App\User\Domain\Factory\Event\UserConfirmedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserUpdatedEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final class ConfirmUserCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface $userRepository;
    private EventBusInterface $eventBus;
    private UuidFactory $mockUuidFactory;
    private UserConfirmedEventFactoryInterface $mockUserConfirmedEventFactory;
    private UserUpdatedEventFactoryInterface $mockUserUpdatedEventFactory;
    private UuidFactory $uuidFactory;
    private UserConfirmedEventFactoryInterface $userConfirmedEventFactory;
    private ConfirmUserCommandFactoryInterface $confirmUserCommandFactory;
    private UuidTransformer $uuidTransformer;
    private UserFactoryInterface $userFactory;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    private GetUserQueryHandler $getUserQueryHandler;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initMocks();
        $this->initFactories();
    }

    public function testInvoke(): void
    {
        $user = $this->createUser();
        $token = $this->createToken($user);

        $this->testInvokeSetExpectations($user, $token);

        $command = $this->confirmUserCommandFactory->create($token);
        $this->getHandler()->__invoke($command);

        $this->assertTrue($user->isConfirmed());
    }

    public function testInvokeUserNotFound(): void
    {
        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->willThrowException(new UserNotFoundException());

        $this->expectException(UserNotFoundException::class);

        $token = $this->confirmationTokenFactory->create(
            $this->faker->uuid(),
        );
        $command = $this->confirmUserCommandFactory->create($token);
        $this->getHandler()->__invoke($command);
    }

    private function getHandler(): ConfirmUserCommandHandler
    {
        return new ConfirmUserCommandHandler(
            $this->getUserQueryHandler,
            $this->userRepository,
            $this->eventBus,
            $this->mockUuidFactory,
            $this->mockUserConfirmedEventFactory,
            $this->mockUserUpdatedEventFactory
        );
    }

    private function testInvokeSetExpectations(
        UserInterface $user,
        ConfirmationTokenInterface $token
    ): void {
        $this->expectUserQuery($user);
        $this->expectEventBusPublish();
        $this->expectUuidFactory();
        $this->expectUserConfirmedEvent($token);
        $this->expectUserUpdatedEvent($user);
    }

    private function createUser(): UserInterface
    {
        $email = $this->faker->email();
        $name = $this->faker->name();
        $password = $this->faker->password();
        $userId = $this->uuidTransformer->transformFromString($this->faker->uuid());

        return $this->userFactory->create($email, $name, $password, $userId);
    }

    private function createToken(UserInterface $user): ConfirmationTokenInterface
    {
        return $this->confirmationTokenFactory->create($user->getId());
    }

    private function initMocks(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->mockUuidFactory = $this->createMock(UuidFactory::class);
        $this->mockUserConfirmedEventFactory = $this->createMock(
            UserConfirmedEventFactoryInterface::class
        );
        $this->mockUserUpdatedEventFactory = $this->createMock(
            UserUpdatedEventFactoryInterface::class
        );
        $this->getUserQueryHandler = $this->createMock(GetUserQueryHandler::class);
    }

    private function initFactories(): void
    {
        $this->uuidFactory = new UuidFactory();
        $this->userConfirmedEventFactory = new UserConfirmedEventFactory();
        $this->uuidTransformer = new UuidTransformer(
            new UuidFactoryInterface()
        );
        $this->userFactory = new UserFactory();
        $this->confirmUserCommandFactory = new ConfirmUserCommandFactory();
        $this->confirmationTokenFactory = new ConfirmationTokenFactory(
            $this->faker->numberBetween(1, 10)
        );
    }

    private function expectUserQuery(UserInterface $user): void
    {
        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->willReturn($user);
    }

    private function expectEventBusPublish(): void
    {
        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with(
                $this->isInstanceOf(UserConfirmedEvent::class),
                $this->isInstanceOf(UserUpdatedEvent::class)
            );
    }

    private function expectUuidFactory(): void
    {
        $this->mockUuidFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->uuidFactory->create());
    }

    private function expectUserConfirmedEvent(ConfirmationTokenInterface $token): void
    {
        $this->mockUserConfirmedEventFactory->expects($this->once())
            ->method('create')
            ->willReturn(
                $this->userConfirmedEventFactory->create(
                    $token,
                    $this->faker->uuid()
                )
            );
    }

    private function expectUserUpdatedEvent(UserInterface $user): void
    {
        $this->mockUserUpdatedEventFactory->expects($this->once())
            ->method('create')
            ->with($user, null, $this->anything())
            ->willReturn(new UserUpdatedEvent(
                $user->getId(),
                $user->getEmail(),
                null,
                $this->faker->uuid()
            ));
    }
}
