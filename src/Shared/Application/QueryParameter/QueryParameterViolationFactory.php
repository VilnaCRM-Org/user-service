<?php

declare(strict_types=1);

namespace App\Shared\Application\QueryParameter;

use function sprintf;

final class QueryParameterViolationFactory
{
    private const UNKNOWN_PARAMETER_TITLE = 'Invalid query parameter';
    private const INVALID_PAGINATION_TITLE = 'Invalid pagination value';
    private const INVALID_PAGINATION_DETAIL =
        'Page and itemsPerPage must be greater than or equal to 1.';

    public function unknownParameters(string $parameterList): QueryParameterViolation
    {
        return new QueryParameterViolation(
            self::UNKNOWN_PARAMETER_TITLE,
            sprintf('Unknown query parameter(s): %s', $parameterList)
        );
    }

    public function invalidPagination(): QueryParameterViolation
    {
        return new QueryParameterViolation(
            self::INVALID_PAGINATION_TITLE,
            self::INVALID_PAGINATION_DETAIL
        );
    }
}
