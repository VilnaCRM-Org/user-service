<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener\QueryParameter\Pagination;

use App\Shared\Application\EventListener\QueryParameter\QueryParameterViolation;
use App\Shared\Application\EventListener\QueryParameter\QueryParameterViolationFactory;

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
        $normalized = $this->normalizer->normalize($value);

        if (
            $this->isMissingButSent($value)
            || $this->isInvalidNormalizedValue($value, $normalized)
        ) {
            return $this->invalidPaginationViolation();
        }

        return null;
    }

    private function isMissingButSent(mixed $value): bool
    {
        $isProvided = $this->valueEvaluator->isExplicitlyProvided($value);
        $wasSent = $this->valueEvaluator->wasParameterSent($value);

        return !$isProvided && $wasSent;
    }

    private function isInvalidNormalizedValue(
        mixed $value,
        ?int $normalized
    ): bool {
        return $this->valueEvaluator->isExplicitlyProvided($value)
            && ($normalized === null || $normalized > self::MAX_ITEMS_PER_PAGE);
    }

    private function invalidPaginationViolation(): QueryParameterViolation
    {
        return $this->violationFactory->invalidPagination();
    }
}
