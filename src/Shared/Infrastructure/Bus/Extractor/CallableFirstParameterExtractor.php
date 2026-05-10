<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Extractor;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\Shared\Infrastructure\Bus\InvokeParameterExtractor;

final readonly class CallableFirstParameterExtractor
{
    public function __construct(
        private InvokeParameterExtractor $extractor
    ) {
    }

    /**
     * @param iterable<DomainEventSubscriberInterface> $callables
     *
     * @return array<array<DomainEventSubscriberInterface>>
     *
     * @psalm-return array<string, list{DomainEventSubscriberInterface}>
     */
    public function forCallables(iterable $callables): array
    {
        $callableArray = iterator_to_array($callables);

        $keys = array_map(
            fn (object $handler): ?string => $this->extractor->extract($handler),
            $callableArray
        );

        $values = array_map(
            static fn ($value) => [$value],
            $callableArray
        );

        return array_combine($keys, $values);
    }

    /**
     * @param iterable<DomainEventSubscriberInterface> $callables
     *
     * @return array<int, array<DomainEventSubscriberInterface>>
     */
    public function forPipedCallables(iterable $callables): array
    {
        return array_reduce(
            iterator_to_array($callables),
            $this->pipedCallablesReducer(),
            []
        );
    }

    /**
     * @psalm-return \Closure(array<string, list<DomainEventSubscriberInterface>>, DomainEventSubscriberInterface):array<string, list<DomainEventSubscriberInterface>>
     */
    private function pipedCallablesReducer(): \Closure
    {
        return static fn (
            /** @var array<string, list<DomainEventSubscriberInterface>> $subscribers */
            array $subscribers,
            DomainEventSubscriberInterface $subscriber
        ): array => array_reduce(
            $subscriber->subscribedTo(),
            /** @psalm-suppress ForbiddenCode - typed via outer @psalm-return */
            static fn (
                /** @var array<string, list<DomainEventSubscriberInterface>> $carry */
                array $carry,
                string $event
            ) => self::addSubscriberToEvent($carry, $event, $subscriber),
            $subscribers
        );
    }

    /**
     * @param array<string, list<DomainEventSubscriberInterface>> $subscribers
     *
     * @return array<string, list<DomainEventSubscriberInterface>>
     */
    private static function addSubscriberToEvent(
        array $subscribers,
        string $event,
        DomainEventSubscriberInterface $subscriber
    ): array {
        $subscribers[$event][] = $subscriber;

        return $subscribers;
    }
}
