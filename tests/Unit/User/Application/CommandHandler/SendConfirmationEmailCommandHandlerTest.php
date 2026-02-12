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
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\ConfirmationEmailFactory;
use App\User\Domain\Factory\ConfirmationEmailFactoryInterface;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactory;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
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
    private TokenRepositoryInterface $tokenRepository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initMocks();
        $this->initFactories();

        $this->handler = new SendConfirmationEmailCommandHandler(
            $this->eventBus,
            $this->tokenRepository,
            $this->mockUuidFactory
        );
    }

    public function testInvoke(): void
    {
        [$command, $token] = $this->createCommandPayload();

        $this->expectUuidFactory();
        $this->expectPublish();
        $this->expectTokenSave($token);

        $this->handler->__invoke($command);
    }

    /**
     * @return (ConfirmationTokenInterface|\App\User\Application\Command\SendConfirmationEmailCommand)[]
     *
     * @psalm-return list{\App\User\Application\Command\SendConfirmationEmailCommand, ConfirmationTokenInterface}
     */
    private function createCommandPayload(): array
    {
        $user = $this->createUser();
        $token = $this->confirmationTokenFactory->create($user->getId());
        $confirmationEmail = $this->confirmationEmailFactory->create($token, $user);
        $command = $this->commandFactory->create($confirmationEmail);

        return [$command, $token];
    }

    private function createUser(): UserInterface
    {
        $email = $this->faker->email();
        $name = $this->faker->name();
        $password = $this->faker->password();
        $userId = $this->uuidTransformer->transformFromString($this->faker->uuid());

        return $this->userFactory->create($email, $name, $password, $userId);
    }

    private function expectUuidFactory(): void
    {
        $this->mockUuidFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->uuidFactory->create());
    }

    private function expectPublish(): void
    {
        $this->eventBus->expects($this->once())
            ->method('publish');
    }

    private function expectTokenSave(ConfirmationTokenInterface $token): void
    {
        $this->tokenRepository->expects($this->once())
            ->method('save')
            ->with($token);
    }

    private function initMocks(): void
    {
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->mockUuidFactory = $this->createMock(UuidFactory::class);
        $this->tokenRepository = $this->createMock(TokenRepositoryInterface::class);
    }

    private function initFactories(): void
    {
        $this->uuidFactory = new UuidFactory();
        $this->eventFactory = new ConfirmationEmailSendEventFactory();
        $this->userFactory = new UserFactory();
        $this->confirmationTokenFactory = new ConfirmationTokenFactory(
            $this->faker->numberBetween(1, 10)
        );
        $this->confirmationEmailFactory = new ConfirmationEmailFactory($this->eventFactory);
        $this->uuidTransformer = new UuidTransformer(new UuidFactoryInterface());
        $this->commandFactory = new SendConfirmationEmailCommandFactory();
    }
}
