<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Pagination;

use App\Shared\Application\Factory\QueryParameterViolationFactory;
use App\Shared\Application\QueryParameter\Evaluator\ExplicitValueEvaluator;
use App\Shared\Application\QueryParameter\Normalizer\PositiveIntegerNormalizer;
use App\Shared\Application\QueryParameter\QueryParameterViolation;

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

    /**
     * @param array<string, scalar|null>|scalar|null $value
     */
    private function violationForExplicit(
        mixed $value
    ): ?QueryParameterViolation {
        if ($this->normalizer->normalize($value) !== null) {
            return null;
        }

        return $this->violationFactory->invalidPagination();
    }

    /**
     * @param array<string, scalar|null>|scalar|null $value
     */
    private function violationForImplicit(
        mixed $value
    ): ?QueryParameterViolation {
        if (!$this->valueEvaluator->wasParameterSent($value)) {
            return null;
        }

        return $this->violationFactory->invalidPagination();
    }
}
