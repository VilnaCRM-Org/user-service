<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Decorator;

use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\CommandHandler\RequestPasswordResetHandlerInterface;
use App\User\Domain\Exception\PasswordResetRateLimitExceededException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class RateLimitedRequestPasswordResetHandler implements
    RequestPasswordResetHandlerInterface
{
    public function __construct(
        private RequestPasswordResetHandlerInterface $inner,
        private RateLimiterFactory $rateLimiter,
    ) {
    }

    #[\Override]
    public function __invoke(RequestPasswordResetCommand $command): void
    {
        $this->checkRateLimit($command->email);
        $this->inner->__invoke($command);
    }

    private function checkRateLimit(string $key): void
    {
        $limiter = $this->rateLimiter->create($key);

        if (!$limiter->consume(1)->isAccepted()) {
            throw new PasswordResetRateLimitExceededException();
        }
    }
}
