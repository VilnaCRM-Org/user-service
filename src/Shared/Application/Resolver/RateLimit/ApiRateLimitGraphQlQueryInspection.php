<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\RateLimit;

use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\OperationDefinitionNode;

final readonly class ApiRateLimitGraphQlQueryInspection
{
    private ApiRateLimitGraphQlRootFields $rootFields;
    private ApiRateLimitGraphQlFieldValueResolver $fieldValueResolver;

    /**
     * @param array<string, FragmentDefinitionNode> $fragments
     */
    public function __construct(
        OperationDefinitionNode $operation,
        array $fragments,
    ) {
        $this->rootFields = new ApiRateLimitGraphQlRootFields($operation, $fragments);
        $this->fieldValueResolver = new ApiRateLimitGraphQlFieldValueResolver();
    }

    /**
     * @param list<string> $fieldNames
     */
    public function containsMutationField(array $fieldNames): bool
    {
        return $this->countMutationFields($fieldNames) > 0;
    }

    /**
     * @param list<string> $fieldNames
     */
    public function countMutationFields(array $fieldNames): int
    {
        return count($this->rootFields->matchingMutationFields($fieldNames));
    }

    /**
     * @param list<string> $keys
     */
    public function findArgumentStringValue(array $keys): ?string
    {
        return $this->fieldValueResolver->findArgumentStringValue($this->rootFields->all(), $keys);
    }

    /**
     * @param list<string> $keys
     */
    public function findArgumentVariableName(array $keys): ?string
    {
        return $this->fieldValueResolver->findArgumentVariableName($this->rootFields->all(), $keys);
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null> $variables
     * @param list<string> $fieldNames
     * @param list<string> $keys
     *
     * @return list<string>
     */
    public function findStringValuesForMutationFields(
        array $variables,
        array $fieldNames,
        array $keys
    ): array {
        return $this->fieldValueResolver->findStringValuesForFields(
            $this->rootFields->matchingMutationFields($fieldNames),
            $variables,
            $keys
        );
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null> $variables
     * @param list<string> $keys
     */
    public function findArgumentVariableValue(array $variables, array $keys): ?string
    {
        return $this->fieldValueResolver->findArgumentVariableValue(
            $this->rootFields->all(),
            $variables,
            $keys
        );
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null> $variables
     * @param list<string> $keys
     */
    public function findInputObjectVariableValue(array $variables, array $keys): ?string
    {
        return $this->fieldValueResolver->findInputObjectVariableValue(
            $this->rootFields->all(),
            $variables,
            $keys
        );
    }

    /**
     * @return list<string>
     */
    public function inputVariableNames(): array
    {
        return $this->fieldValueResolver->inputVariableNames($this->rootFields->all());
    }
}
