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
        if (!$this->hasAllowedParametersForPath($path)) {
            return null;
        }

        $unknownParameters = $this->findUnknownParameters($path, $query);

        if ($unknownParameters === []) {
            return null;
        }

        return $this->violationFactory->unknownParameters(
            implode(', ', $unknownParameters)
        );
    }

    private function hasAllowedParametersForPath(string $path): bool
    {
        return isset($this->allowedParameters[$path]);
    }

    /**
     * @param array<string, array|string|int|float|bool|null> $query
     *
     * @return array<int, string>
     */
    private function findUnknownParameters(string $path, array $query): array
    {
        $allowedForPath = $this->allowedParameters[$path];
        $providedParameters = array_keys($query);

        return array_diff($providedParameters, $allowedForPath);
    }
}
