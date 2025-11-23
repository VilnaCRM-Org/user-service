<?php

declare(strict_types=1);

namespace App\Shared\Application\QueryParameter\Pagination;

use App\Shared\Application\QueryParameter\QueryParameterViolation;
use App\Shared\Application\QueryParameter\QueryParameterViolationFactory;

use function array_key_exists;

final class PageParameterValidator
{
    public function __construct(
        private readonly ExplicitValueEvaluator $valueEvaluator,
        private readonly PositiveIntegerNormalizer $normalizer,
        private readonly QueryParameterViolationFactory $violationFactory
    ) {
    }

    /**
     * @param array<string, array|string|int|float|bool|null> $query
     */
    public function validate(array $query): ?QueryParameterViolation
    {
        return match (true) {
            !array_key_exists('page', $query) => null,
            $this->valueEvaluator->isExplicitlyProvided(
                $query['page']
            ) => $this->violationForExplicit(
                $query['page']
            ),
            default => $this->violationForImplicit($query['page']),
        };
    }

    private function violationForExplicit(
        mixed $value
    ): ?QueryParameterViolation {
        if ($this->normalizer->normalize($value) !== null) {
            return null;
        }

        return $this->violationFactory->invalidPagination();
    }

    private function violationForImplicit(
        mixed $value
    ): ?QueryParameterViolation {
        if (!$this->valueEvaluator->wasParameterSent($value)) {
            return null;
        }

        return $this->violationFactory->invalidPagination();
    }
}
