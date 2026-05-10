<?php

declare(strict_types=1);

namespace App\Internal\HealthCheck\Infrastructure\EventSubscriber;

use App\Internal\HealthCheck\Domain\Event\HealthCheckEvent;
use Symfony\Contracts\Cache\CacheInterface;

final class CacheCheckSubscriber extends BaseHealthCheckSubscriber
{
    public function __construct(private readonly CacheInterface $cache)
    {
    }

    #[\Override]
    public function onHealthCheck(HealthCheckEvent $event): void
    {
        $this->cache->get('health_check', static fn (
        ) => self::cacheMissHandler());
    }

    private static function cacheMissHandler(): string
    {
        return 'ok';
    }
}
