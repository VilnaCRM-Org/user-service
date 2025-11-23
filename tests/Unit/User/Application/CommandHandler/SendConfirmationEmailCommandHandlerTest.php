<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as UuidFactoryInterface;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\CommandHandler\SendConfirmationEmailCommandHandler;
use App\User\Application\Factory\SendConfirmationEmailCommandFactory;
use App\User\Application\Factory\SendConfirmationEmailCommandFactoryInterface;
use App\User\Domain\Factory\ConfirmationEmailFactory;
use App\User\Domain\Factory\ConfirmationEmailFactoryInterface;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactory;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final class SendConfirmationEmailCommandHandlerTest extends UnitTestCase
{
    private SendConfirmationEmailCommandHandler $handler;
    private EventBusInterface $eventBus;
    private UuidFactory $uuidFactory;
    private UuidFactory $mockUuidFactory;
    private ConfirmationEmailSendEventFactoryInterface $eventFactory;
    private UserFactoryInterface $userFactory;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    private ConfirmationEmailFactoryInterface $confirmationEmailFactory;
    private UuidTransformer $uuidTransformer;
    private SendConfirmationEmailCommandFactoryInterface $commandFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->uuidFactory = new UuidFactory();
        $this->mockUuidFactory = $this->createMock(UuidFactory::class);
        $this->eventFactory = new ConfirmationEmailSendEventFactory();
        $this->userFactory = new UserFactory();
        $this->confirmationTokenFactory = new ConfirmationTokenFactory(
            $this->faker->numberBetween(1, 10)
        );
        $this->confirmationEmailFactory =
            new ConfirmationEmailFactory($this->eventFactory);
        $this->uuidTransformer =
            new UuidTransformer(new UuidFactoryInterface());
        $this->commandFactory = new SendConfirmationEmailCommandFactory();

        $this->handler = new SendConfirmationEmailCommandHandler(
            $this->eventBus,
            $this->mockUuidFactory
        );
    }

    public function testInvoke(): void
    {
        $email = $this->faker->email();
        $name = $this->faker->name();
        $password = $this->faker->password();
        $userId =
            $this->uuidTransformer->transformFromString($this->faker->uuid());

        $this->mockUuidFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->uuidFactory->create());

        $user = $this->userFactory->create($email, $name, $password, $userId);
        $token = $this->confirmationTokenFactory->create($user->getId());

        $confirmationEmail =
            $this->confirmationEmailFactory->create($token, $user);

        $command = $this->commandFactory->create($confirmationEmail);

        $this->eventBus->expects($this->once())
            ->method('publish');

        $this->handler->__invoke($command);
    }
}
