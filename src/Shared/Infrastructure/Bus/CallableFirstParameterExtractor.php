<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use function array_combine;
use function array_map;
use function array_reduce;
use function iterator_to_array;
use LogicException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

final class CallableFirstParameterExtractor
{
    /**
     * @param iterable<DomainEventSubscriberInterface> $callables
     *
     * @return array<int, string|null>
     */
    public function forCallables(iterable $callables): array
    {
        $callableArray = iterator_to_array($callables);

        $keys = array_map(
            fn (callable $handler): ?string => $this->extract($handler),
            $callableArray
        );

        $values = array_map(static fn ($value) => [$value], $callableArray);

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
            static function (
                array $subscribers,
                DomainEventSubscriberInterface $subscriber
            ): array {
                foreach ($subscriber->subscribedTo() as $event) {
                    $subscribers[$event][] = $subscriber;
                }

                return $subscribers;
            },
            []
        );
    }

    public function extract(object|string $class): ?string
    {
        $reflector = new ReflectionClass($class);
        $method = $reflector->getMethod('__invoke');

        if ($method->getNumberOfParameters() !== 1) {
            return null;
        }

        return $this->firstParameterClassFrom($method);
    }

    private function firstParameterClassFrom(ReflectionMethod $method): string
    {
        /** @var ReflectionNamedType|null $firstParameterType */
        $firstParameterType = $method->getParameters()[0]->getType();

        if ($firstParameterType === null) {
            throw new LogicException(
                'Missing type hint for the first parameter of __invoke'
            );
        }

        return $firstParameterType->getName();
    }
}
