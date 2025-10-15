<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener\QueryParameter\Pagination;

use App\Shared\Application\EventListener\QueryParameter\QueryParameterRule;
use App\Shared\Application\EventListener\QueryParameter\QueryParameterViolation;

/**
 * @implements QueryParameterRule
 */
final class PaginationRule implements QueryParameterRule
{
    private const USERS_PATH = '/api/users';

    public function __construct(
        private readonly PageParameterValidator $pageValidator,
        private readonly ItemsPerPageParameterValidator $itemsPerPageValidator
    ) {
    }

    /**
     * @param array<string, array|string|int|float|bool|null> $query
     */
    public function validate(
        string $path,
        array $query
    ): ?QueryParameterViolation {
        if ($path !== self::USERS_PATH) {
            return null;
        }

        return $this->pageValidator->validate($query)
            ?? $this->itemsPerPageValidator->validate($query);
    }
}
