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
     * @param array<string, array|string|int|float|bool|null> $queryParameters
     */
    #[\Override]
    public function validate(
        string $path,
        array $queryParameters
    ): ?QueryParameterViolation {
        $pathHasNoRestrictions = !$this->pathHasAllowedParametersDefined($path);
        if ($pathHasNoRestrictions) {
            return null;
        }

        $unknownParameters = $this->extractUnknownParameters(
            $path,
            $queryParameters
        );

        $allParametersAreValid = $unknownParameters === [];
        if ($allParametersAreValid) {
            return null;
        }

        return $this->createViolationForUnknownParameters($unknownParameters);
    }

    private function pathHasAllowedParametersDefined(string $path): bool
    {
        return isset($this->allowedParameters[$path]);
    }

    /**
     * @param array<string, array|string|int|float|bool|null> $queryParameters
     *
     * @return string[]
     *
     * @psalm-return array<int<0, max>, string>
     */
    private function extractUnknownParameters(
        string $path,
        array $queryParameters
    ): array {
        $allowedForPath = $this->allowedParameters[$path];
        $providedParameters = array_keys($queryParameters);

        return array_diff($providedParameters, $allowedForPath);
    }

    /**
     * @param array<int, string> $unknownParameters
     */
    private function createViolationForUnknownParameters(
        array $unknownParameters
    ): QueryParameterViolation {
        $parameterNames = implode(', ', $unknownParameters);

        return $this->violationFactory->unknownParameters($parameterNames);
    }
}
