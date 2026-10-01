<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventSubscriber;

use App\Shared\Infrastructure\Factory\RedisIamConnectionFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;

/**
 * Re-authenticates IAM Redis connections at safe points, before any other
 * listener of the event can use Redis: before a main request, when a worker
 * starts, before a worker handles a message and on every worker loop
 * iteration, including idle ones. The idle iteration matters because
 * StopWorkerOnRestartSignalListener (priority 0) reads the restart signal
 * from cache.app, a Redis pool, on every WorkerRunningEvent. Failures
 * propagate, so nothing runs on a connection whose authentication could not
 * be renewed.
 */
final readonly class RedisIamConnectionRenewalSubscriber implements EventSubscriberInterface
{
    private const BEFORE_OTHER_LISTENERS_PRIORITY = 4096;

    public function __construct(
        private RedisIamConnectionFactory $connectionFactory
    ) {
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     *
     * @psalm-return array{'kernel.request': list{'onKernelRequest', 4096}, WorkerStartedEvent::class: list{'renewDueConnections', 4096}, WorkerMessageReceivedEvent::class: list{'renewDueConnections', 4096}, WorkerRunningEvent::class: list{'renewDueConnections', 4096}}
     */
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        $renewal = ['renewDueConnections', self::BEFORE_OTHER_LISTENERS_PRIORITY];

        return [
            KernelEvents::REQUEST => ['onKernelRequest', self::BEFORE_OTHER_LISTENERS_PRIORITY],
            WorkerStartedEvent::class => $renewal,
            WorkerMessageReceivedEvent::class => $renewal,
            WorkerRunningEvent::class => $renewal,
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->renewDueConnections();
    }

    public function renewDueConnections(): void
    {
        foreach ($this->connectionFactory->connections() as $connection) {
            $connection->renewIfDue();
        }
    }
}
