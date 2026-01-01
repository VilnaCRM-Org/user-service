<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\Command\ConfirmPasswordResetCommandResponse;
use App\User\Application\CommandHandler\ConfirmPasswordResetCommandHandler;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Contract\PasswordResetTokenValidatorInterface;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\PasswordResetConfirmedEvent;
use App\User\Domain\Exception\PasswordResetTokenAlreadyUsedException;
use App\User\Domain\Exception\PasswordResetTokenExpiredException;
use App\User\Domain\Exception\PasswordResetTokenNotFoundException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\Event\PasswordResetConfirmedEventFactoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Uuid;

final class ConfirmPasswordResetCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PasswordResetTokenRepositoryInterface&MockObject $tokenRepository;
    private PasswordHasherInterface&MockObject $passwordHasher;
    private EventBusInterface&MockObject $eventBus;
    private UuidFactory&MockObject $uuidFactory;
    private PasswordResetTokenValidatorInterface&MockObject $tokenValidator;
    private PasswordResetConfirmedEventFactoryInterface&MockObject $eventFactory;
    private ConfirmPasswordResetCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->tokenRepository = $this->createMock(PasswordResetTokenRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactory::class);
        $this->tokenValidator = $this->createMock(PasswordResetTokenValidatorInterface::class);
        $this->eventFactory = $this->createMock(PasswordResetConfirmedEventFactoryInterface::class);

        $this->handler = new ConfirmPasswordResetCommandHandler(
            $this->tokenRepository,
            $this->userRepository,
            $this->passwordHasher,
            $this->eventBus,
            $this->uuidFactory,
            $this->tokenValidator,
            $this->eventFactory,
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
            ->method('getUserID')
            ->willReturn($userId);

        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('getId')
            ->willReturn($userId);

        $event = $this->createMock(PasswordResetConfirmedEvent::class);

        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn($passwordResetToken);

        $this->tokenValidator->expects($this->once())
            ->method('validate')
            ->with($passwordResetToken);

        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->with($newPassword)
            ->willReturn('hashed_password');

        $user->expects($this->once())
            ->method('setPassword')
            ->with('hashed_password');

        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($user);

        $this->tokenRepository->expects($this->once())
            ->method('save')
            ->with($passwordResetToken);

        $this->uuidFactory->expects($this->once())
            ->method('create')
            ->willReturn($eventId);

        $this->eventFactory->expects($this->once())
            ->method('create')
            ->with($userId, (string) $eventId)
            ->willReturn($event);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($event);

        $this->handler->__invoke($command);

        $response = $command->getResponse();
        $this->assertInstanceOf(ConfirmPasswordResetCommandResponse::class, $response);
        $this->assertEquals('', $response->message);
    }

    public function testInvokePublishesEventWithCorrectUserId(): void
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
            ->method('getUserID')
            ->willReturn($userId);

        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('getId')
            ->willReturn($userId);

        $event = $this->createMock(PasswordResetConfirmedEvent::class);

        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn($passwordResetToken);

        $this->tokenValidator->expects($this->once())
            ->method('validate')
            ->with($passwordResetToken);

        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->with($newPassword)
            ->willReturn('hashed_password');

        $user->expects($this->once())
            ->method('setPassword')
            ->with('hashed_password');

        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($user);

        $this->tokenRepository->expects($this->once())
            ->method('save')
            ->with($passwordResetToken);

        $this->uuidFactory->expects($this->once())
            ->method('create')
            ->willReturn($eventId);

        $this->eventFactory->expects($this->once())
            ->method('create')
            ->with($userId, (string) $eventId)
            ->willReturn($event);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($event);

        $this->handler->__invoke($command);
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
            ->method('getUserID')
            ->willReturn($userId);

        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($token)
            ->willReturn($passwordResetToken);

        $this->tokenValidator->expects($this->once())
            ->method('validate')
            ->with($passwordResetToken);

        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);

        $this->handler->__invoke($command);
    }
}
