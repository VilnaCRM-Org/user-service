<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Bus\Extractor\CallableFirstParameterExtractor;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
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
     * @return array<array<object|HandlerDescriptor>>
     *
     * @psalm-return array<int|string, array<object|HandlerDescriptor>>
     */
    private function buildHandlersMap(iterable $callables): array
    {
        $callableArray = iterator_to_array($callables);

        $subscribers = $this->filterSubscribers($callableArray);

        $regularHandlers = array_filter(
            $callableArray,
            static fn (object $handler): bool => !$handler instanceof DomainEventSubscriberInterface
        );

        // DomainEventSubscribers use subscribedTo() for routing
        $subscriberMap = $this->createSubscriberHandlerMap($subscribers);

        // Regular handlers use __invoke parameter type for routing
        // Note: Unmappable handlers get null keys, but Symfony's HandlersLocator
        // never looks up by null, so they're effectively ignored
        $handlerMap = $this->extractor->forCallables($regularHandlers);

        return array_merge_recursive($subscriberMap, $handlerMap);
    }

    /**
     * @param array<object> $callables
     *
     * @return list<DomainEventSubscriberInterface>
     */
    private function filterSubscribers(array $callables): array
    {
        $subscribers = [];

        foreach ($callables as $callable) {
            if ($callable instanceof DomainEventSubscriberInterface) {
                $subscribers[] = $callable;
            }
        }

        return $subscribers;
    }

    /**
     * @param list<DomainEventSubscriberInterface> $subscribers
     *
     * @return array<string, list<HandlerDescriptor>>
     */
    private function createSubscriberHandlerMap(array $subscribers): array
    {
        $handlersMap = [];

        foreach ($this->extractor->forPipedCallables($subscribers) as $event => $eventSubscribers) {
            $handlersMap[$event] = array_map(
                fn (
                    DomainEventSubscriberInterface $subscriber
                ): HandlerDescriptor => $this->createSubscriberHandlerDescriptor($subscriber),
                $eventSubscribers
            );
        }

        return $handlersMap;
    }

    private function createSubscriberHandlerDescriptor(
        DomainEventSubscriberInterface $subscriber
    ): HandlerDescriptor {
        return new HandlerDescriptor($subscriber, [
            'alias' => \sprintf('%s#%d', $subscriber::class, \spl_object_id($subscriber)),
        ]);
    }
}
