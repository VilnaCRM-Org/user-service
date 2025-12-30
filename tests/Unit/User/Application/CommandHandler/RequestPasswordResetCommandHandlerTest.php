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
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Uuid;

final class RequestPasswordResetCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PasswordResetTokenRepositoryInterface&MockObject $passwordResetTokenRepository;
    private PasswordResetTokenFactoryInterface&MockObject $passwordResetTokenFactory;
    private EventBusInterface&MockObject $eventBus;
    private UuidFactory&MockObject $uuidFactory;
    private RateLimiterFactory&MockObject $rateLimiterFactory;
    private LimiterInterface&MockObject $limiter;
    private RateLimit&MockObject $rateLimit;

    private RequestPasswordResetCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordResetTokenRepository = $this->createMock(PasswordResetTokenRepositoryInterface::class);
        $this->passwordResetTokenFactory = $this->createMock(PasswordResetTokenFactoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactory::class);
        $this->rateLimiterFactory = $this->createMock(RateLimiterFactory::class);
        $this->limiter = $this->createMock(LimiterInterface::class);
        $this->rateLimit = $this->createMock(RateLimit::class);

        $this->handler = new RequestPasswordResetCommandHandler(
            $this->userRepository,
            $this->passwordResetTokenRepository,
            $this->passwordResetTokenFactory,
            $this->eventBus,
            $this->uuidFactory,
            $this->rateLimiterFactory,
        );
    }

    public function testRequestPasswordResetForExistingUser(): void
    {
        $email = $this->faker->email();
        $userId = $this->faker->uuid();
        $tokenValue = $this->faker->sha256();
        $uuid = Uuid::fromString($this->faker->uuid());

        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn($userId);

        $token = $this->createMock(PasswordResetTokenInterface::class);
        $token->method('getTokenValue')->willReturn($tokenValue);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->rateLimiterFactory
            ->expects($this->once())
            ->method('create')
            ->with($email)
            ->willReturn($this->limiter);

        $this->limiter
            ->expects($this->once())
            ->method('consume')
            ->with(1)
            ->willReturn($this->rateLimit);

        $this->rateLimit
            ->expects($this->once())
            ->method('isAccepted')
            ->willReturn(true);

        $this->passwordResetTokenFactory
            ->expects($this->once())
            ->method('create')
            ->with($userId)
            ->willReturn($token);

        $this->passwordResetTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($token);

        $this->uuidFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($uuid);

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(PasswordResetRequestedEvent::class));

        $command = new RequestPasswordResetCommand($email);
        $this->handler->__invoke($command);

        $this->assertSame('', $command->getResponse()->message);
    }

    public function testRequestPasswordResetForNonExistingUser(): void
    {
        $email = $this->faker->email();

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->rateLimiterFactory
            ->expects($this->never())
            ->method('create');

        $this->passwordResetTokenFactory
            ->expects($this->never())
            ->method('create');

        $this->eventBus
            ->expects($this->never())
            ->method('publish');

        $command = new RequestPasswordResetCommand($email);
        $this->handler->__invoke($command);

        $this->assertSame('', $command->getResponse()->message);
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

        $this->rateLimiterFactory
            ->expects($this->once())
            ->method('create')
            ->with($email)
            ->willReturn($this->limiter);

        $this->limiter
            ->expects($this->once())
            ->method('consume')
            ->with(1)
            ->willReturn($this->rateLimit);

        $this->rateLimit
            ->expects($this->once())
            ->method('isAccepted')
            ->willReturn(false);

        $this->passwordResetTokenFactory
            ->expects($this->never())
            ->method('create');

        $this->eventBus
            ->expects($this->never())
            ->method('publish');

        $this->expectException(PasswordResetRateLimitExceededException::class);

        $command = new RequestPasswordResetCommand($email);
        $this->handler->__invoke($command);
    }
}
