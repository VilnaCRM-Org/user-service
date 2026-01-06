<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Pagination;

use App\Shared\Application\QueryParameter\Pagination\ItemsPerPageRule;
use App\Shared\Application\QueryParameter\QueryParameterViolation;

use function array_key_exists;

final class ItemsPerPageParameterValidator
{
    public function __construct(private readonly ItemsPerPageRule $rule)
    {
    }

    /**
     * @param array<string, array|string|int|float|bool|null> $query
     */
    public function validate(array $query): ?QueryParameterViolation
    {
        if (!array_key_exists('itemsPerPage', $query)) {
            return null;
        }

        return $this->rule->evaluate($query['itemsPerPage']);
    }
}
