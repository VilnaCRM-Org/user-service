<?php

declare(strict_types=1);

namespace App\Shared\Application\Collector;

use App\Shared\Application\Resolver\BatchEmailResolver;
use Traversable;

final class BatchEmailCollector
{
    public function __construct(
        private readonly BatchEmailResolver $resolver
    ) {
    }

    /**
     * @param iterable<array-key, array|object|string|int|float|bool|null> $entries
     */
    public function collect(iterable $entries): BatchEmailCollection
    {
        $items = $this->toArray($entries);

        [$emails, $hasMissing] = $this->splitEmails($items);

        return new BatchEmailCollection($emails, $hasMissing);
    }

    /**
     * @param iterable<array-key, array|object|string|int|float|bool|null> $entries
     *
     * @return array<int, array|object|string|int|float|bool|null>
     */
    private function toArray(iterable $entries): array
    {
        if (! ($entries instanceof Traversable)) {
            assert(is_array($entries));

            return $entries;
        }

        $items = [];
        foreach ($entries as $entry) {
            $items[] = $entry;
        }

        return $items;
    }

    /**
     * @param array<int, array|object|string|int|float|bool|null> $entries
     *
     * @return (bool|mixed|string[])[]
     *
     * @psalm-return array{0?: list<string>|mixed, 1?: bool|mixed,...}
     */
    private function splitEmails(array $entries): array
    {
        return array_reduce(
            $entries,
            function (array $carry, array|object|string|int|float|bool|null $entry): array {
                if (!is_array($entry)) {
                    $carry[1] = true;

                    return $carry;
                }

                $normalizedEmail = $this->resolver->resolve($entry);

                if ($normalizedEmail === null) {
                    $carry[1] = true;

                    return $carry;
                }

                $carry[0][] = $normalizedEmail;

                return $carry;
            },
            [[], false]
        );
    }
}
