<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * @psalm-api
 *
 * @codeCoverageIgnore Empty listener — replaces rate limiter in load_test env
 */
final readonly class UnlimitedApiRateLimitListener
{
    /** @psalm-suppress UnusedParam */
    public function __invoke(RequestEvent $event): void
    {
    }
}
