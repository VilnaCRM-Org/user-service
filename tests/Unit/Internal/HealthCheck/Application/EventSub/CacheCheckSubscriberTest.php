<?php

declare(strict_types=1);

namespace App\Tests\Unit\Internal\HealthCheck\Application\EventSub;

use App\Internal\HealthCheck\Application\EventSub\CacheCheckSubscriber;
use App\Internal\HealthCheck\Domain\Event\HealthCheckEvent;
use App\Tests\Unit\UnitTestCase;
use Symfony\Contracts\Cache\CacheInterface;

final class CacheCheckSubscriberTest extends UnitTestCase
{
    private CacheInterface $cache;
    private CacheCheckSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = $this->createMock(CacheInterface::class);
        $this->subscriber = new CacheCheckSubscriber($this->cache);
    }

    public function testOnHealthCheck(): void
    {
        $this->cache->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo('health_check'),
                $this->isInstanceOf(\Closure::class)
            )
            ->willReturn('ok');

        $event = new HealthCheckEvent();
        $this->subscriber->onHealthCheck($event);
    }

    public function testCacheMissHandler(): void
    {
        // Test the static method via reflection since it's private
        $reflection = new \ReflectionClass(CacheCheckSubscriber::class);
        $method = $reflection->getMethod('cacheMissHandler');
        $this->makeAccessible($method);

        $result = $method->invoke(null);
        $this->assertSame('ok', $result);
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [HealthCheckEvent::class => 'onHealthCheck'],
            CacheCheckSubscriber::getSubscribedEvents()
        );
    }
}
