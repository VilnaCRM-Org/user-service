<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\SendConfirmationEmailCommandFactory;
use App\User\Application\Factory\SendConfirmationEmailCommandFactoryInterface;
use App\User\Application\Resolver\ResendEmailMutationResolver;
use App\User\Domain\Factory\ConfirmationEmailFactory;
use App\User\Domain\Factory\ConfirmationEmailFactoryInterface;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactory;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;

class ResendEmailMutationResolverTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    private ConfirmationEmailSendEventFactoryInterface $eventFactory;
    private ConfirmationEmailFactoryInterface $confirmationEmailFactory;
    private SendConfirmationEmailCommandFactoryInterface $emailCommandFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer();
        $this->confirmationTokenFactory = new ConfirmationTokenFactory($this->faker->numberBetween(1, 10));
        $this->eventFactory = new ConfirmationEmailSendEventFactory();
        $this->confirmationEmailFactory = new ConfirmationEmailFactory($this->eventFactory);
        $this->emailCommandFactory = new SendConfirmationEmailCommandFactory();
    }

    public function testInvoke(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $tokenFactory = $this->createMock(ConfirmationTokenFactoryInterface::class);
        $mockConfirmationEmailFactory = $this->createMock(ConfirmationEmailFactoryInterface::class);
        $mockEmailCommandFactory = $this->createMock(SendConfirmationEmailCommandFactoryInterface::class);

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

        $resolver = new ResendEmailMutationResolver(
            $commandBus,
            $tokenRepository,
            $tokenFactory,
            $mockConfirmationEmailFactory,
            $mockEmailCommandFactory
        );

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

        $result = $resolver->__invoke($user, []);

        $this->assertSame($user, $result);
    }

    public function testInvokeWithNonExistingToken(): void
    {
        $commandBus = $this->createMock(CommandBusInterface::class);
        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $tokenFactory = $this->createMock(ConfirmationTokenFactoryInterface::class);
        $mockConfirmationEmailFactory = $this->createMock(ConfirmationEmailFactoryInterface::class);
        $mockEmailCommandFactory = $this->createMock(SendConfirmationEmailCommandFactoryInterface::class);

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

        $resolver = new ResendEmailMutationResolver(
            $commandBus,
            $tokenRepository,
            $tokenFactory,
            $mockConfirmationEmailFactory,
            $mockEmailCommandFactory
        );

        $tokenRepository->expects($this->once())
            ->method('findByUserId')
            ->with($this->equalTo($userId))
            ->willReturn(null);

        $tokenFactory->expects($this->once())
            ->method('create')
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

        $result = $resolver->__invoke($user, []);

        $this->assertSame($user, $result);
    }
}
