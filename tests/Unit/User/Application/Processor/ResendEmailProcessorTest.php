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
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\ConfirmationEmailFactory;
use App\User\Domain\Factory\ConfirmationEmailFactoryInterface;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactory;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

final class ResendEmailProcessorTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    private ConfirmationEmailFactoryInterface $confirmationFactory;
    private SendConfirmationEmailCommandFactoryInterface $emailCommandFactory;
    private CommandBusInterface $commandBus;
    private UserRepositoryInterface $userRepository;
    private TokenRepositoryInterface $tokenRepository;
    private ConfirmationEmailFactoryInterface $mockConfirmationEmailFactory;
    private SendConfirmationEmailCommandFactoryInterface $mockEmailCmdFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer();
        $this->confirmationTokenFactory = new ConfirmationTokenFactory(
            $this->faker->numberBetween(1, 10)
        );
        $this->confirmationFactory = new ConfirmationEmailFactory(
            new ConfirmationEmailSendEventFactory()
        );
        $this->emailCommandFactory = new SendConfirmationEmailCommandFactory();
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->userRepository =
            $this->createMock(UserRepositoryInterface::class);
        $this->tokenRepository =
            $this->createMock(TokenRepositoryInterface::class);
        $this->mockConfirmationEmailFactory =
            $this->createMock(ConfirmationEmailFactoryInterface::class);
        $this->mockEmailCmdFactory = $this->createMock(
            SendConfirmationEmailCommandFactoryInterface::class
        );
    }

    public function testProcess(): void
    {
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

        $this->testProcessSetExpectations($user, $token);

        $response = $this->getProcessor()->process(
            new RetryDto(),
            $this->createMock(Operation::class),
            ['id' => $userId]
        );

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testProcessUserNotFound(): void
    {
        $userId = $this->faker->uuid();
        $retryDto = new RetryDto();

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with($this->equalTo($userId))
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);

        $this->getProcessor()->process(
            $retryDto,
            $this->createMock(Operation::class),
            ['id' => $userId]
        );
    }

    private function getProcessor(): ResendEmailProcessor
    {
        return new ResendEmailProcessor(
            $this->commandBus,
            $this->userRepository,
            $this->tokenRepository,
            $this->createMock(ConfirmationTokenFactoryInterface::class),
            $this->mockConfirmationEmailFactory,
            $this->mockEmailCmdFactory
        );
    }

    private function testProcessSetExpectations(
        UserInterface $user,
        ConfirmationTokenInterface $token
    ): void {
        $confirmationEmail = $this->confirmationFactory->create($token, $user);
        $command = $this->emailCommandFactory->create($confirmationEmail);

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with($this->equalTo($user->getID()))
            ->willReturn($user);

        $this->tokenRepository->expects($this->once())
            ->method('findByUserId')
            ->with($this->equalTo($user->getID()))
            ->willReturn($token);

        $this->mockConfirmationEmailFactory->expects($this->once())
            ->method('create')
            ->willReturn($confirmationEmail);

        $this->mockEmailCmdFactory->expects($this->once())
            ->method('create')
            ->with($confirmationEmail)
            ->willReturn($command);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }
}
