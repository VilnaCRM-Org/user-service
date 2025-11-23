<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\Command\ConfirmPasswordResetCommandResponse;
use App\User\Application\CommandHandler\ConfirmPasswordResetCommandHandler;
use App\User\Application\Service\UserPasswordService;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\PasswordResetConfirmedEvent;
use App\User\Domain\Exception\PasswordResetTokenAlreadyUsedException;
use App\User\Domain\Exception\PasswordResetTokenExpiredException;
use App\User\Domain\Exception\PasswordResetTokenNotFoundException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\Service\PasswordResetTokenValidatorInterface;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Uuid;

final class ConfirmPasswordResetCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface $userRepository;
    private PasswordResetTokenRepositoryInterface $tokenRepository;
    private UserPasswordService $passwordService;
    private EventBusInterface $eventBus;
    private UuidFactory $uuidFactory;
    private PasswordResetTokenValidatorInterface $tokenValidator;
    private ConfirmPasswordResetCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->tokenRepository = $this->createMock(PasswordResetTokenRepositoryInterface::class);
        $this->passwordService = $this->createMock(UserPasswordService::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactory::class);
        $this->tokenValidator = $this->createMock(PasswordResetTokenValidatorInterface::class);

        $this->handler = new ConfirmPasswordResetCommandHandler(
            $this->tokenRepository,
            $this->userRepository,
            $this->passwordService,
            $this->eventBus,
            $this->uuidFactory,
            $this->tokenValidator
        );
    }

    public function testInvokeSuccessfully(): void
    {
        $token = $this->faker->lexify('??????????');
        $newPassword = $this->faker->password(12);
        $userId = $this->faker->uuid();
        $eventId = Uuid::v4();

        $command = new ConfirmPasswordResetCommand($token, $newPassword);

        $passwordResetToken = $this->createMock(PasswordResetTokenInterface::class);
        $passwordResetToken->expects($this->once())
            ->method('markAsUsed');
        $passwordResetToken->expects($this->once())
            ->method('getUserId')
            ->willReturn($userId);

        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('getId')
            ->willReturn($userId);

        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn($passwordResetToken);

        $this->tokenValidator->expects($this->once())
            ->method('validate')
            ->with($passwordResetToken);

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);

        $this->passwordService->expects($this->once())
            ->method('updateUserPassword')
            ->with($user, $newPassword);

        $this->tokenRepository->expects($this->once())
            ->method('save')
            ->with($passwordResetToken);

        $this->uuidFactory->expects($this->once())
            ->method('create')
            ->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(PasswordResetConfirmedEvent::class));

        $this->handler->__invoke($command);

        $response = $command->getResponse();
        $this->assertInstanceOf(ConfirmPasswordResetCommandResponse::class, $response);
        $this->assertEquals('', $response->message);
    }

    public function testInvokeThrowsExceptionWhenTokenNotFound(): void
    {
        $token = $this->faker->lexify('??????????');
        $newPassword = $this->faker->password(12);

        $command = new ConfirmPasswordResetCommand($token, $newPassword);

        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn(null);

        $this->tokenValidator->expects($this->once())
            ->method('validate')
            ->with(null)
            ->willThrowException(new PasswordResetTokenNotFoundException());

        $this->expectException(PasswordResetTokenNotFoundException::class);

        $this->handler->__invoke($command);
    }

    public function testInvokeThrowsExceptionWhenTokenExpired(): void
    {
        $token = $this->faker->lexify('??????????');
        $newPassword = $this->faker->password(12);

        $command = new ConfirmPasswordResetCommand($token, $newPassword);

        $passwordResetToken = $this->createMock(PasswordResetTokenInterface::class);

        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn($passwordResetToken);

        $this->tokenValidator->expects($this->once())
            ->method('validate')
            ->with($passwordResetToken)
            ->willThrowException(new PasswordResetTokenExpiredException());

        $this->expectException(PasswordResetTokenExpiredException::class);

        $this->handler->__invoke($command);
    }

    public function testInvokeThrowsExceptionWhenTokenAlreadyUsed(): void
    {
        $token = $this->faker->lexify('??????????');
        $newPassword = $this->faker->password(12);

        $command = new ConfirmPasswordResetCommand($token, $newPassword);

        $passwordResetToken = $this->createMock(PasswordResetTokenInterface::class);

        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn($passwordResetToken);

        $this->tokenValidator->expects($this->once())
            ->method('validate')
            ->with($passwordResetToken)
            ->willThrowException(new PasswordResetTokenAlreadyUsedException());

        $this->expectException(PasswordResetTokenAlreadyUsedException::class);

        $this->handler->__invoke($command);
    }

    public function testInvokeThrowsExceptionWhenUserNotFound(): void
    {
        $token = $this->faker->lexify('??????????');
        $newPassword = $this->faker->password(12);

        $command = new ConfirmPasswordResetCommand($token, $newPassword);

        $passwordResetToken = $this->createMock(PasswordResetTokenInterface::class);
        $userId = $this->faker->uuid();

        $passwordResetToken->expects($this->once())
            ->method('getUserId')
            ->willReturn($userId);

        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn($passwordResetToken);

        $this->tokenValidator->expects($this->once())
            ->method('validate')
            ->with($passwordResetToken);

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);

        $this->handler->__invoke($command);
    }
}
