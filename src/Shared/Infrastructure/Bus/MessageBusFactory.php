<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

final readonly class MessageBusFactory
{
    public function __construct(
        private CallableFirstParameterExtractor $extractor
    ) {
    }

    /**
     * @param iterable<object> $callables
     */
    public function create(iterable $callables): MessageBus
    {
        return new MessageBus([$this->getMiddleWare($callables)]);
    }

    /**
     * @param iterable<object> $callables
     */
    private function getMiddleWare(iterable $callables): HandleMessageMiddleware
    {
        return new HandleMessageMiddleware(
            new HandlersLocator(
                $this->buildHandlersMap($callables)
            )
        );
    }

    /**
     * @param iterable<object> $callables
     *
     * @return array<array<DomainEventSubscriberInterface>>
     *
     * @psalm-return array<int|string, array<DomainEventSubscriberInterface>>
     */
    private function buildHandlersMap(iterable $callables): array
    {
        $callableArray = iterator_to_array($callables);

        $subscribers = array_filter(
            $callableArray,
            static fn (object $handler): bool => $handler instanceof DomainEventSubscriberInterface
        );

        $regularHandlers = array_filter(
            $callableArray,
            static fn (object $handler): bool => !$handler instanceof DomainEventSubscriberInterface
        );

        // DomainEventSubscribers use subscribedTo() for routing
        $subscriberMap = $this->extractor->forPipedCallables($subscribers);

        // Regular handlers use __invoke parameter type for routing
        // Note: Unmappable handlers get null keys, but Symfony's HandlersLocator
        // never looks up by null, so they're effectively ignored
        $handlerMap = $this->extractor->forCallables($regularHandlers);

        return array_merge_recursive($subscriberMap, $handlerMap);
    }
}
