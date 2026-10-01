<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventSubscriber;

use App\Shared\Infrastructure\Adapter\RedisIamConnection;
use App\Shared\Infrastructure\EventSubscriber\RedisIamConnectionRenewalSubscriber;
use App\Shared\Infrastructure\Factory\RedisIamConnectionFactory;
use App\Tests\Unit\UnitTestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\EventListener\StopWorkerOnRestartSignalListener;

final class RedisIamConnectionRenewalSubscriberTest extends UnitTestCase
{
    public function testSubscribesToEveryRequestAndWorkerSafePointWithHighPriority(): void
    {
        self::assertSame(
            [
                KernelEvents::REQUEST => ['onKernelRequest', 4096],
                WorkerStartedEvent::class => ['renewDueConnections', 4096],
                WorkerMessageReceivedEvent::class => ['renewDueConnections', 4096],
                WorkerRunningEvent::class => ['renewDueConnections', 4096],
            ],
            RedisIamConnectionRenewalSubscriber::getSubscribedEvents()
        );
    }

    public function testRenewsBeforeTheRestartSignalListenerReadsTheCachePool(): void
    {
        $subscriber = new RedisIamConnectionRenewalSubscriber($this->factoryWith());
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new StopWorkerOnRestartSignalListener(
            $this->createMock(CacheItemPoolInterface::class)
        ));
        $dispatcher->addSubscriber($subscriber);

        foreach ([WorkerStartedEvent::class, WorkerRunningEvent::class] as $eventName) {
            $listeners = $dispatcher->getListeners($eventName);

            self::assertCount(2, $listeners);
            self::assertSame([$subscriber, 'renewDueConnections'], $listeners[0]);
            self::assertInstanceOf(StopWorkerOnRestartSignalListener::class, $listeners[1][0]);
        }
    }

    public function testRenewsEveryConnectionBeforeMainRequest(): void
    {
        $subscriber = new RedisIamConnectionRenewalSubscriber(
            $this->factoryWith(
                $this->connectionExpectingRenewals(1),
                $this->connectionExpectingRenewals(1)
            )
        );

        $subscriber->onKernelRequest($this->requestEvent(HttpKernelInterface::MAIN_REQUEST));
    }

    public function testSkipsSubRequests(): void
    {
        $subscriber = new RedisIamConnectionRenewalSubscriber(
            $this->factoryWith($this->connectionExpectingRenewals(0))
        );

        $subscriber->onKernelRequest($this->requestEvent(HttpKernelInterface::SUB_REQUEST));
    }

    public function testRenewsEveryConnectionBeforeWorkerMessage(): void
    {
        $subscriber = new RedisIamConnectionRenewalSubscriber(
            $this->factoryWith($this->connectionExpectingRenewals(1))
        );

        $subscriber->renewDueConnections();
    }

    public function testRenewalFailurePropagates(): void
    {
        $connection = $this->createMock(RedisIamConnection::class);
        $connection->method('renewIfDue')->willThrowException(new \RedisException('WRONGPASS'));
        $subscriber = new RedisIamConnectionRenewalSubscriber($this->factoryWith($connection));

        $this->expectException(\RedisException::class);

        $subscriber->renewDueConnections();
    }

    private function connectionExpectingRenewals(int $renewals): RedisIamConnection
    {
        $connection = $this->createMock(RedisIamConnection::class);
        $connection->expects(self::exactly($renewals))->method('renewIfDue');

        return $connection;
    }

    private function factoryWith(RedisIamConnection ...$connections): RedisIamConnectionFactory
    {
        $factory = $this->createMock(RedisIamConnectionFactory::class);
        $factory->method('connections')->willReturn(array_values($connections));

        return $factory;
    }

    private function requestEvent(int $requestType): RequestEvent
    {
        return new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            $requestType
        );
    }
}
