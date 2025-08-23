<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\CommandHandler\RequestPasswordResetCommandHandler;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordResetRequestedEvent;
use App\User\Domain\Exception\PasswordResetRateLimitExceededException;
use App\User\Domain\Factory\PasswordResetTokenFactoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Factory\UuidFactory;

final class RequestPasswordResetCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PasswordResetTokenRepositoryInterface&MockObject $passwordResetTokenRepository;
    private PasswordResetTokenFactoryInterface&MockObject $passwordResetTokenFactory;
    private EventBusInterface&MockObject $eventBus;
    private UuidFactory&MockObject $uuidFactory;
    private RequestPasswordResetCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordResetTokenRepository = $this->createMock(PasswordResetTokenRepositoryInterface::class);
        $this->passwordResetTokenFactory = $this->createMock(PasswordResetTokenFactoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactory::class);

        $this->handler = new RequestPasswordResetCommandHandler(
            $this->userRepository,
            $this->passwordResetTokenRepository,
            $this->passwordResetTokenFactory,
            $this->eventBus,
            $this->uuidFactory,
            3, // rateLimitMaxRequests
            1  // rateLimitWindowHours
        );
    }

    public function testRequestPasswordResetForExistingUser(): void
    {
        $email = $this->faker->email();
        $userId = $this->faker->uuid();
        $tokenValue = $this->faker->sha256();

        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn($userId);

        $token = $this->createMock(PasswordResetTokenInterface::class);
        $token->method('getTokenValue')->willReturn($tokenValue);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->passwordResetTokenRepository
            ->expects($this->once())
            ->method('countRecentRequestsByEmail')
            ->willReturn(0);

        $this->passwordResetTokenFactory
            ->expects($this->once())
            ->method('create')
            ->with($userId)
            ->willReturn($token);

        $this->passwordResetTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($token);

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(PasswordResetRequestedEvent::class));

        $command = new RequestPasswordResetCommand($email);
        $this->handler->__invoke($command);

        $this->assertStringContainsString('If valid', $command->getResponse()->message);
    }

    public function testRequestPasswordResetForNonExistingUser(): void
    {
        $email = $this->faker->email();

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->passwordResetTokenRepository
            ->expects($this->never())
            ->method('countRecentRequestsByEmail');

        $this->passwordResetTokenFactory
            ->expects($this->never())
            ->method('create');

        $this->eventBus
            ->expects($this->never())
            ->method('publish');

        $command = new RequestPasswordResetCommand($email);
        $this->handler->__invoke($command);

        $this->assertStringContainsString('If valid', $command->getResponse()->message);
    }

    public function testRequestPasswordResetRateLimitExceeded(): void
    {
        $email = $this->faker->email();

        $user = $this->createMock(UserInterface::class);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->passwordResetTokenRepository
            ->expects($this->once())
            ->method('countRecentRequestsByEmail')
            ->willReturn(3); // Rate limit exceeded

        $this->expectException(PasswordResetRateLimitExceededException::class);

        $command = new RequestPasswordResetCommand($email);
        $this->handler->__invoke($command);
    }
}
