<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Pagination;

use App\Shared\Application\Factory\QueryParameterViolationFactory;
use App\Shared\Application\QueryParameter\Normalizer\BooleanNormalizer;
use App\Shared\Application\QueryParameter\QueryParameterViolation;
use App\Shared\Application\QueryParameter\Validator\ExplicitValueValidator;

use function array_key_exists;

final class PartialParameterValidator
{
    public function __construct(
        private readonly ExplicitValueValidator $valueValidator,
        private readonly BooleanNormalizer $normalizer,
        private readonly QueryParameterViolationFactory $violationFactory
    ) {
    }

    /**
     * @param array<string, array|string|int|float|bool|null> $query
     */
    public function validate(array $query): ?QueryParameterViolation
    {
        if (!array_key_exists('partial', $query)) {
            return null;
        }

        return $this->validatePartialValue($query['partial']);
    }

    /**
     * @param array<string, scalar|null>|scalar|null $partialValue
     */
    private function validatePartialValue(
        array|string|int|float|bool|null $partialValue,
    ): ?QueryParameterViolation {
        if (!$this->valueValidator->wasParameterSent($partialValue)) {
            return null;
        }

        if (!$this->valueValidator->isExplicitlyProvided($partialValue)) {
            return $this->violationFactory->invalidPartialPagination();
        }

        return $this->normalizer->normalize($partialValue) === null
            ? $this->violationFactory->invalidPartialPagination()
            : null;
    }
}
