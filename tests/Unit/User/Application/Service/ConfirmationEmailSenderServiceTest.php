<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Service;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SendConfirmationEmailCommand;
use App\User\Application\Factory\SendConfirmationEmailCommandFactoryInterface;
use App\User\Application\Service\ConfirmationEmailSenderService;
use App\User\Domain\Contract\ConfirmationEmailInterface;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\ConfirmationEmailFactoryInterface;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\TokenRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;

final class ConfirmationEmailSenderServiceTest extends UnitTestCase
{
    private CommandBusInterface&MockObject $commandBus;
    private TokenRepositoryInterface&MockObject $tokenRepository;
    private ConfirmationTokenFactoryInterface&MockObject $tokenFactory;
    private ConfirmationEmailFactoryInterface&MockObject $confirmationEmailFactory;
    private SendConfirmationEmailCommandFactoryInterface&MockObject $emailCmdFactory;
    private ConfirmationEmailSenderService $service;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $this->tokenFactory = $this->createMock(ConfirmationTokenFactoryInterface::class);
        $this->confirmationEmailFactory = $this->createMock(
            ConfirmationEmailFactoryInterface::class
        );
        $this->emailCmdFactory = $this->createMock(
            SendConfirmationEmailCommandFactoryInterface::class
        );
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());

        $this->service = new ConfirmationEmailSenderService(
            $this->commandBus,
            $this->tokenRepository,
            $this->tokenFactory,
            $this->confirmationEmailFactory,
            $this->emailCmdFactory
        );
    }

    public function testSendWithExistingToken(): void
    {
        $user = $this->createUser();
        $existingToken = $this->createMock(ConfirmationTokenInterface::class);
        $confirmationEmail = $this->createMock(ConfirmationEmailInterface::class);
        $command = $this->createMock(SendConfirmationEmailCommand::class);

        $this->tokenRepository->expects($this->once())
            ->method('findByUserId')
            ->with($user->getId())
            ->willReturn($existingToken);

        $this->tokenFactory->expects($this->never())->method('create');

        $this->setupSendExpectations($user, $existingToken, $confirmationEmail, $command);

        $this->service->send($user);
    }

    public function testSendWithNewTokenWhenNoExistingToken(): void
    {
        $user = $this->createUser();
        $newToken = $this->createMock(ConfirmationTokenInterface::class);
        $confirmationEmail = $this->createMock(ConfirmationEmailInterface::class);
        $command = $this->createMock(SendConfirmationEmailCommand::class);

        $this->tokenRepository->expects($this->once())
            ->method('findByUserId')
            ->with($user->getId())
            ->willReturn(null);

        $this->tokenFactory->expects($this->once())
            ->method('create')
            ->with($user->getId())
            ->willReturn($newToken);

        $this->setupSendExpectations($user, $newToken, $confirmationEmail, $command);

        $this->service->send($user);
    }

    private function setupSendExpectations(
        User $user,
        ConfirmationTokenInterface $token,
        ConfirmationEmailInterface $email,
        SendConfirmationEmailCommand $command
    ): void {
        $this->confirmationEmailFactory->expects($this->once())
            ->method('create')
            ->with($token, $user)
            ->willReturn($email);

        $this->emailCmdFactory->expects($this->once())
            ->method('create')
            ->with($email)
            ->willReturn($command);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }

    private function createUser(): User
    {
        return $this->userFactory->create(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
    }
}
