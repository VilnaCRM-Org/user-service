<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventSubscriber;

use App\Shared\Infrastructure\Factory\RedisIamConnectionFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;

/**
 * Re-authenticates IAM Redis connections at safe points: before a main
 * request and before a worker handles a message. Failures propagate, so a
 * request never runs on a connection whose authentication could not be
 * renewed.
 */
final readonly class RedisIamConnectionRenewalSubscriber implements EventSubscriberInterface
{
    private const BEFORE_REQUEST_LISTENERS_PRIORITY = 4096;

    public function __construct(
        private RedisIamConnectionFactory $connectionFactory
    ) {
    }

    /**
     * @return array<string, array{0: string, 1: int}|string>
     *
     * @psalm-return array{'kernel.request': list{'onKernelRequest', 4096}, WorkerMessageReceivedEvent::class: 'renewDueConnections'}
     */
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', self::BEFORE_REQUEST_LISTENERS_PRIORITY],
            WorkerMessageReceivedEvent::class => 'renewDueConnections',
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
