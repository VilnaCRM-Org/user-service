<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener\QueryParameter;

interface QueryParameterRule
{
    /**
     * @param array<string, array|string|int|float|bool|null> $query
     */
    public function validate(
        string $path,
        array $query
    ): ?QueryParameterViolation;
}
