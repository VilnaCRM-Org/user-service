<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\RateLimit;

final readonly class ApiRateLimitNestedPayloadStringResolver
{
    /**
     * @param array<array-key, array|string|int|float|bool|null> $payload
     * @param list<string> $keys
     */
    public function resolve(array $payload, array $keys): ?string
    {
        foreach ($payload as $key => $value) {
            $resolved = $this->resolveEntry($key, $value, $keys);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null>|string|int|float|bool|null $value
     * @param list<string> $keys
     */
    private function resolveEntry(
        int|string $key,
        array|string|int|float|bool|null $value,
        array $keys
    ): ?string {
        if (is_string($key) && $this->isMatchingStringValue($key, $value, $keys)) {
            return $value;
        }

        return is_array($value) ? $this->resolve($value, $keys) : null;
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null>|string|int|float|bool|null $value
     * @param list<string> $keys
     */
    private function isMatchingStringValue(
        string $key,
        array|string|int|float|bool|null $value,
        array $keys
    ): bool {
        return in_array($key, $keys, true) && is_string($value) && $value !== '';
    }
}
