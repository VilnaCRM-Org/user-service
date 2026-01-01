<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Decorator;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\CommandHandler\RequestPasswordResetHandlerInterface;
use App\User\Domain\Exception\PasswordResetRateLimitExceededException;
use App\User\Infrastructure\Decorator\RateLimitedRequestPasswordResetHandler;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final class RateLimitedRequestPasswordResetHandlerTest extends UnitTestCase
{
    private RequestPasswordResetHandlerInterface&MockObject $innerHandler;
    private RateLimiterFactory&MockObject $rateLimiterFactory;
    private LimiterInterface&MockObject $limiter;
    private RateLimit&MockObject $rateLimit;

    private RateLimitedRequestPasswordResetHandler $decorator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->innerHandler = $this->createMock(RequestPasswordResetHandlerInterface::class);
        $this->rateLimiterFactory = $this->createMock(RateLimiterFactory::class);
        $this->limiter = $this->createMock(LimiterInterface::class);
        $this->rateLimit = $this->createMock(RateLimit::class);

        $this->decorator = new RateLimitedRequestPasswordResetHandler(
            $this->innerHandler,
            $this->rateLimiterFactory,
        );
    }

    public function testDelegatesWhenRateLimitAccepted(): void
    {
        $email = $this->faker->email();
        $command = new RequestPasswordResetCommand($email);

        $this->expectRateLimitCheck($email, accepted: true);

        $this->innerHandler
            ->expects($this->once())
            ->method('__invoke')
            ->with($command);

        $this->decorator->__invoke($command);
    }

    public function testThrowsExceptionWhenRateLimitExceeded(): void
    {
        $email = $this->faker->email();
        $command = new RequestPasswordResetCommand($email);

        $this->expectRateLimitCheck($email, accepted: false);

        $this->innerHandler
            ->expects($this->never())
            ->method('__invoke');

        $this->expectException(PasswordResetRateLimitExceededException::class);

        $this->decorator->__invoke($command);
    }

    private function expectRateLimitCheck(string $email, bool $accepted): void
    {
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
            ->willReturn($accepted);
    }
}
