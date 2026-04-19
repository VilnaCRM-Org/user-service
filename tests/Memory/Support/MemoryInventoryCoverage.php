<?php

declare(strict_types=1);

namespace App\Tests\Memory\Support;

final class MemoryInventoryCoverage
{
    /**
     * @param list<string> $expectedItems
     * @param list<string> $accountedItems
     */
    public static function percentage(array $expectedItems, array $accountedItems): int
    {
        $expectedItems = self::normalize($expectedItems);
        if ($expectedItems === []) {
            return 100;
        }

        $matchedItems = array_intersect($expectedItems, self::normalize($accountedItems));
        $matchedCount = count($matchedItems);
        $expectedCount = count($expectedItems);

        if ($matchedCount === $expectedCount) {
            return 100;
        }

        return (int) floor($matchedCount / $expectedCount * 100);
    }

    /**
     * @param list<string> $expectedItems
     * @param list<string> $accountedItems
     *
     * @return list<string>
     */
    public static function missing(array $expectedItems, array $accountedItems): array
    {
        return array_values(
            array_diff(self::normalize($expectedItems), self::normalize($accountedItems))
        );
    }

    /**
     * @param list<string> $expectedItems
     * @param list<string> $accountedItems
     *
     * @return list<string>
     */
    public static function unexpected(array $expectedItems, array $accountedItems): array
    {
        return array_values(
            array_diff(self::normalize($accountedItems), self::normalize($expectedItems))
        );
    }

    /**
     * @param list<string> $items
     *
     * @return list<string>
     */
    private static function normalize(array $items): array
    {
        $items = array_values(array_unique($items));
        sort($items);

        return $items;
    }
}
