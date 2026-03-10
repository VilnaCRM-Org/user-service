<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SendConfirmationEmailCommand;
use App\User\Application\Factory\SendConfirmationEmailCommandFactoryInterface;
use App\User\Application\Resolver\ResendEmailMutationResolver;
use App\User\Domain\Aggregate\ConfirmationEmailInterface;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\ConfirmationEmailFactory;
use App\User\Domain\Factory\ConfirmationEmailFactoryInterface;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactory;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;

final class ResendEmailMutationResolverTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    private ConfirmationEmailFactoryInterface $confirmationEmailFactory;
    private CommandBusInterface $commandBus;
    private TokenRepositoryInterface $tokenRepository;
    private ConfirmationTokenFactoryInterface $tokenFactory;
    private ConfirmationEmailFactoryInterface $mockConfirmationEmailFactory;
    private SendConfirmationEmailCommandFactoryInterface $mockEmailCmdFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());
        $this->confirmationTokenFactory = new ConfirmationTokenFactory(
            $this->faker->numberBetween(1, 10)
        );
        $this->confirmationEmailFactory = new ConfirmationEmailFactory(
            new ConfirmationEmailSendEventFactory()
        );
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->tokenRepository =
            $this->createMock(TokenRepositoryInterface::class);
        $this->tokenFactory =
            $this->createMock(ConfirmationTokenFactoryInterface::class);
        $this->mockConfirmationEmailFactory =
            $this->createMock(ConfirmationEmailFactoryInterface::class);
        $this->mockEmailCmdFactory = $this->createMock(
            SendConfirmationEmailCommandFactoryInterface::class
        );
    }

    public function testInvoke(): void
    {
        $user = $this->getUser();
        $token = $this->confirmationTokenFactory->create($user->getID());
        $confirmationEmail = $this->confirmationEmailFactory->create(
            $token,
            $user
        );
        $command = $this->mockEmailCmdFactory->create($confirmationEmail);

        $this->setInvokeExpectations(
            $user,
            $token,
            $confirmationEmail,
            $command
        );

        $result = $this->getResolver()->__invoke($user, []);

        $this->assertSame($user, $result);
    }

    public function testInvokeWithNonExistingToken(): void
    {
        $user = $this->getUser();
        $token = $this->confirmationTokenFactory->create($user->getID());
        $confirmationEmail = $this->confirmationEmailFactory->create(
            $token,
            $user
        );
        $command = $this->mockEmailCmdFactory->create($confirmationEmail);

        $this->setInvokeWithNonExistingTokenExpectations(
            $user,
            $token,
            $confirmationEmail,
            $command
        );

        $result = $this->getResolver()->__invoke($user, []);

        $this->assertSame($user, $result);
    }

    private function setInvokeExpectations(
        UserInterface $user,
        ConfirmationTokenInterface $token,
        ConfirmationEmailInterface $confirmationEmail,
        SendConfirmationEmailCommand $command
    ): void {
        $userId = $user->getID();
        $this->tokenRepository->expects($this->once())
            ->method('findByUserId')
            ->with($this->equalTo($userId))
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

    private function setInvokeWithNonExistingTokenExpectations(
        UserInterface $user,
        ConfirmationTokenInterface $token,
        ConfirmationEmailInterface $confirmationEmail,
        SendConfirmationEmailCommand $command
    ): void {
        $userId = $user->getID();

        $this->tokenRepository->expects($this->once())
            ->method('findByUserId')
            ->with($this->equalTo($userId))
            ->willReturn(null);

        $this->tokenFactory->expects($this->once())
            ->method('create')
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

    private function getUser(): UserInterface
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userId = $this->faker->uuid();

        return $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->uuidTransformer->transformFromString($userId)
        );
    }

    private function getResolver(): ResendEmailMutationResolver
    {
        return new ResendEmailMutationResolver(
            $this->commandBus,
            $this->tokenRepository,
            $this->tokenFactory,
            $this->mockConfirmationEmailFactory,
            $this->mockEmailCmdFactory
        );
    }
}
