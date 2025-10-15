<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener\QueryParameter;

final class QueryViolationFinder
{
    /**
     * @param iterable<QueryParameterRule> $rules
     * @param array<string, array|string|int|float|bool|null> $query
     */
    public function find(
        iterable $rules,
        string $path,
        array $query
    ): ?QueryParameterViolation {
        foreach ($rules as $rule) {
            $violation = $rule->validate($path, $query);

            if ($violation !== null) {
                return $violation;
            }
        }

        return null;
    }
}
