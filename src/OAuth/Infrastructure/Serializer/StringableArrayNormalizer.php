<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Serializer;

use InvalidArgumentException;

final class StringableArrayNormalizer
{
    /**
     * @param array<int, string|object> $values
     *
     * @return list<string>
     */
    public function normalize(array $values, string $exceptionMessage): array
    {
        $normalized = [];

        foreach ($values as $value) {
            if (is_string($value)) {
                $normalized[] = $value;
                continue;
            }

            if (is_object($value) && method_exists($value, '__toString')) {
                $normalized[] = (string) $value;
                continue;
            }

            throw new InvalidArgumentException($exceptionMessage);
        }

        return $normalized;
    }
}
