<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\Command\ConfirmPasswordResetCommandResponse;
use App\User\Application\CommandHandler\ConfirmPasswordResetCommandHandler;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordResetConfirmedEvent;
use App\User\Domain\Exception\PasswordResetTokenAlreadyUsedException;
use App\User\Domain\Exception\PasswordResetTokenExpiredException;
use App\User\Domain\Exception\PasswordResetTokenNotFoundException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Uuid;

final class ConfirmPasswordResetCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface $userRepository;
    private PasswordResetTokenRepositoryInterface $tokenRepository;
    private PasswordHasherFactoryInterface $hasherFactory;
    private EventBusInterface $eventBus;
    private UuidFactory $uuidFactory;
    private ConfirmPasswordResetCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->tokenRepository = $this->createMock(PasswordResetTokenRepositoryInterface::class);
        $this->hasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactory::class);

        $this->handler = new ConfirmPasswordResetCommandHandler(
            $this->userRepository,
            $this->tokenRepository,
            $this->hasherFactory,
            $this->eventBus,
            $this->uuidFactory
        );
    }

    public function testInvokeSuccessfully(): void
    {
        $token = 'valid-token';
        $newPassword = 'newPassword123!';
        $hashedPassword = 'hashed_password';
        $userId = $this->faker->uuid();
        $eventId = Uuid::v4();

        $command = new ConfirmPasswordResetCommand($token, $newPassword);

        $passwordResetToken = $this->createMock(PasswordResetTokenInterface::class);
        $passwordResetToken->expects($this->once())
            ->method('isExpired')
            ->willReturn(false);
        $passwordResetToken->expects($this->once())
            ->method('isUsed')
            ->willReturn(false);
        $passwordResetToken->expects($this->once())
            ->method('getUserID')
            ->willReturn($userId);
        $passwordResetToken->expects($this->once())
            ->method('markAsUsed');

        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('setPassword')
            ->with($hashedPassword);

        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $passwordHasher->expects($this->once())
            ->method('hash')
            ->with($newPassword)
            ->willReturn($hashedPassword);

        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn($passwordResetToken);

        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($user);

        $this->tokenRepository->expects($this->once())
            ->method('save')
            ->with($passwordResetToken);

        $this->hasherFactory->expects($this->once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($passwordHasher);

        $this->uuidFactory->expects($this->once())
            ->method('create')
            ->willReturn($eventId);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(PasswordResetConfirmedEvent::class));

        $this->handler->__invoke($command);

        $response = $command->getResponse();
        $this->assertInstanceOf(ConfirmPasswordResetCommandResponse::class, $response);
        $this->assertEquals('Password has been reset successfully.', $response->message);
    }

    public function testInvokeThrowsExceptionWhenTokenNotFound(): void
    {
        $token = 'invalid-token';
        $newPassword = 'newPassword123!';

        $command = new ConfirmPasswordResetCommand($token, $newPassword);

        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn(null);

        $this->expectException(PasswordResetTokenNotFoundException::class);

        $this->handler->__invoke($command);
    }

    public function testInvokeThrowsExceptionWhenTokenExpired(): void
    {
        $token = 'expired-token';
        $newPassword = 'newPassword123!';

        $command = new ConfirmPasswordResetCommand($token, $newPassword);

        $passwordResetToken = $this->createMock(PasswordResetTokenInterface::class);
        $passwordResetToken->expects($this->once())
            ->method('isExpired')
            ->willReturn(true);

        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn($passwordResetToken);

        $this->expectException(PasswordResetTokenExpiredException::class);

        $this->handler->__invoke($command);
    }

    public function testInvokeThrowsExceptionWhenTokenAlreadyUsed(): void
    {
        $token = 'used-token';
        $newPassword = 'newPassword123!';

        $command = new ConfirmPasswordResetCommand($token, $newPassword);

        $passwordResetToken = $this->createMock(PasswordResetTokenInterface::class);
        $passwordResetToken->expects($this->once())
            ->method('isExpired')
            ->willReturn(false);
        $passwordResetToken->expects($this->once())
            ->method('isUsed')
            ->willReturn(true);

        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn($passwordResetToken);

        $this->expectException(PasswordResetTokenAlreadyUsedException::class);

        $this->handler->__invoke($command);
    }

    public function testInvokeThrowsExceptionWhenUserNotFound(): void
    {
        $token = 'valid-token';
        $newPassword = 'newPassword123!';
        $userId = $this->faker->uuid();

        $command = new ConfirmPasswordResetCommand($token, $newPassword);

        $passwordResetToken = $this->createMock(PasswordResetTokenInterface::class);
        $passwordResetToken->expects($this->once())
            ->method('isExpired')
            ->willReturn(false);
        $passwordResetToken->expects($this->once())
            ->method('isUsed')
            ->willReturn(false);
        $passwordResetToken->expects($this->once())
            ->method('getUserID')
            ->willReturn($userId);

        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn($passwordResetToken);

        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);

        $this->handler->__invoke($command);
    }
}