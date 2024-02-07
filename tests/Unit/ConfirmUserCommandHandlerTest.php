<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\Command\ConfirmUserCommand;
use App\User\Application\CommandHandler\ConfirmUserCommandHandler;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\UserConfirmedEvent;
use App\User\Domain\Factory\Event\UserConfirmedEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Exception\UserNotFoundException;
use DG\BypassFinals;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

class ConfirmUserCommandHandlerTest extends TestCase
{
    private ConfirmUserCommandHandler $handler;
    private UserRepositoryInterface $userRepository;
    private EventBusInterface $eventBus;
    private UuidFactory $uuidFactory;
    private UserConfirmedEventFactoryInterface $userConfirmedEventFactory;
    private Generator $faker;

    protected function setUp(): void
    {
        BypassFinals::enable(bypassReadOnly: false);
        parent::setUp();

        $this->faker = Factory::create();

        $this->userRepository = $this->createMock(
            UserRepositoryInterface::class
        );
        $this->eventBus = $this->createMock(
            EventBusInterface::class
        );
        $this->uuidFactory = $this->createMock(
            UuidFactory::class
        );
        $this->userConfirmedEventFactory = $this->createMock(
            UserConfirmedEventFactoryInterface::class
        );

        $this->handler = new ConfirmUserCommandHandler(
            $this->userRepository,
            $this->eventBus,
            $this->uuidFactory,
            $this->userConfirmedEventFactory
        );
    }

    public function testInvoke(): void
    {
        $email = $this->faker->email();
        $name = $this->faker->name();
        $password = $this->faker->password();
        $userId = new Uuid($this->faker->uuid());

        $user = new User($email, $name, $password, $userId);
        $token = new ConfirmationToken(
            $this->faker->uuid(),
            $user->getId()
        );

        $this->userRepository->expects($this->once())
            ->method('find')
            ->willReturn($user);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(UserConfirmedEvent::class));

        $this->uuidFactory->expects($this->once())
            ->method('create')
            ->willReturn(new SymfonyUuid($this->faker->uuid()));

        $this->userConfirmedEventFactory->expects($this->once())
            ->method('create')
            ->willReturn(
                new UserConfirmedEvent(
                    $token,
                    $this->faker->uuid()
                )
            );

        $command = new ConfirmUserCommand($token);
        $this->handler->__invoke($command);

        $this->assertTrue($user->isConfirmed());
    }

    public function testInvokeUserNotFound(): void
    {
        $this->userRepository->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);

        $token = new ConfirmationToken(
            $this->faker->uuid(),
            $this->faker->uuid(),
        );
        $command = new ConfirmUserCommand($token);
        $this->handler->__invoke($command);
    }
}
