<?php

declare(strict_types=1);

namespace App\Shared\Application\QueryParameter;

use App\Shared\Application\Factory\QueryParameterViolationFactory;

use function array_diff;
use function array_keys;
use function implode;

/**
 * @implements QueryParameterRule
 */
final class AllowedParametersRule implements QueryParameterRule
{
    /**
     * @param array<string, array<int, string>> $allowedParameters
     */
    public function __construct(
        private readonly array $allowedParameters,
        private readonly QueryParameterViolationFactory $violationFactory
    ) {
    }

    /**
     * @param array<string, array|string|int|float|bool|null> $query
     */
    #[\Override]
    public function validate(
        string $path,
        array $query
    ): ?QueryParameterViolation {
        $allowed = $this->allowedParameters[$path] ?? null;

        return match (true) {
            $allowed === null => null,
            ($unknown = array_diff(
                array_keys($query),
                $allowed
            )) === [] => null,
            default => $this->violationFactory->unknownParameters(
                implode(', ', $unknown)
            ),
        };
    }
}
