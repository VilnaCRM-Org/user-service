<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;

final readonly class UnlimitedApiRateLimitListener
{
    public function __invoke(RequestEvent $event): void
    {
    }
}
