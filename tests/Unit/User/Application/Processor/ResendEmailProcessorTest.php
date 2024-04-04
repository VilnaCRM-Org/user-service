<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Application\Transformer\UuidTransformer;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\RetryDto;
use App\User\Application\Factory\SendConfirmationEmailCommandFactory;
use App\User\Application\Factory\SendConfirmationEmailCommandFactoryInterface;
use App\User\Application\Processor\ResendEmailProcessor;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\ConfirmationEmailFactory;
use App\User\Domain\Factory\ConfirmationEmailFactoryInterface;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactory;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

final class ResendEmailProcessorTest extends UnitTestCase
{
    private Operation $mockOperation;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    private ConfirmationEmailSendEventFactoryInterface $eventFactory;
    private ConfirmationEmailFactoryInterface $confirmationEmailFactory;
    private SendConfirmationEmailCommandFactoryInterface $emailCommandFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockOperation = $this->createMock(Operation::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer();
        $this->confirmationTokenFactory = new ConfirmationTokenFactory($this->faker->numberBetween(1, 10));
        $this->eventFactory = new ConfirmationEmailSendEventFactory();
        $this->confirmationEmailFactory = new ConfirmationEmailFactory($this->eventFactory);
        $this->emailCommandFactory = new SendConfirmationEmailCommandFactory();
    }

    public function testProcess(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $tokenFactory = $this->createMock(ConfirmationTokenFactoryInterface::class);
        $mockConfirmationEmailFactory = $this->createMock(ConfirmationEmailFactoryInterface::class);
        $mockEmailCommandFactory = $this->createMock(SendConfirmationEmailCommandFactoryInterface::class);

        $processor = new ResendEmailProcessor(
            $commandBus,
            $userRepository,
            $tokenRepository,
            $tokenFactory,
            $mockConfirmationEmailFactory,
            $mockEmailCommandFactory
        );

        $retryDto = new RetryDto();

        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userId = $this->faker->uuid();

        $user = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->uuidTransformer->transformFromString($userId)
        );
        $token = $this->confirmationTokenFactory->create($userId);
        $confirmationEmail = $this->confirmationEmailFactory->create(
            $token,
            $user
        );
        $command = $this->emailCommandFactory->create($confirmationEmail);

        $userRepository->expects($this->once())
            ->method('find')
            ->with($this->equalTo($userId))
            ->willReturn($user);

        $tokenRepository->expects($this->once())
            ->method('findByUserId')
            ->with($this->equalTo($userId))
            ->willReturn($token);

        $mockConfirmationEmailFactory->expects($this->once())
            ->method('create')
            ->willReturn($confirmationEmail);

        $mockEmailCommandFactory->expects($this->once())
            ->method('create')
            ->with($confirmationEmail)
            ->willReturn($command);

        $commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);

        $response = $processor->process($retryDto, $this->mockOperation, ['id' => $userId]);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testProcessUserNotFound(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $tokenFactory = $this->createMock(ConfirmationTokenFactoryInterface::class);
        $confirmationEmailFactory = $this->createMock(ConfirmationEmailFactoryInterface::class);
        $emailCmdFactory = $this->createMock(SendConfirmationEmailCommandFactoryInterface::class);

        $processor = new ResendEmailProcessor(
            $commandBus,
            $userRepository,
            $tokenRepository,
            $tokenFactory,
            $confirmationEmailFactory,
            $emailCmdFactory
        );

        $userId = $this->faker->uuid();
        $retryDto = new RetryDto();

        $userRepository->expects($this->once())
            ->method('find')
            ->with($this->equalTo($userId))
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);

        $processor->process($retryDto, $this->mockOperation, ['id' => $userId]);
    }
}
