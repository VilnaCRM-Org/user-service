<?php

declare(strict_types=1);

namespace App\Shared\Application\QueryParameter\Pagination;

use App\Shared\Application\QueryParameter\QueryParameterViolation;
use App\Shared\Application\QueryParameter\QueryParameterViolationFactory;

final class ItemsPerPageRule
{
    private const MAX_ITEMS_PER_PAGE = 100;

    public function __construct(
        private readonly ExplicitValueEvaluator $valueEvaluator,
        private readonly PositiveIntegerNormalizer $normalizer,
        private readonly QueryParameterViolationFactory $violationFactory
    ) {
    }

    public function evaluate(mixed $value): ?QueryParameterViolation
    {
        if (!$this->valueEvaluator->wasParameterSent($value)) {
            return null;
        }

        if (!$this->valueEvaluator->isExplicitlyProvided($value)) {
            return $this->violationFactory->invalidPagination();
        }

        $normalized = $this->normalizer->normalize($value);

        if ($normalized === null || $normalized > self::MAX_ITEMS_PER_PAGE) {
            return $this->violationFactory->invalidPagination();
        }

        return null;
    }
}
