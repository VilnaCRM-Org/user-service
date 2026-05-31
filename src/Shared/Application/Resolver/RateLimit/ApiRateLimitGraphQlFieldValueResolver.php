<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\RateLimit;

use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Language\AST\VariableNode;

final readonly class ApiRateLimitGraphQlFieldValueResolver
{
    private ApiRateLimitNestedPayloadStringResolver $nestedPayloadStringResolver;

    public function __construct()
    {
        $this->nestedPayloadStringResolver = new ApiRateLimitNestedPayloadStringResolver();
    }

    /**
     * @param list<FieldNode> $fields
     * @param list<string> $keys
     */
    public function findArgumentStringValue(array $fields, array $keys): ?string
    {
        $value = $this->findNamedInputValue($fields, $keys);

        return $value instanceof StringValueNode && $value->value !== '' ? $value->value : null;
    }

    /**
     * @param list<FieldNode> $fields
     * @param list<string> $keys
     */
    public function findArgumentVariableName(array $fields, array $keys): ?string
    {
        $value = $this->findNamedInputValue($fields, $keys);

        return $value instanceof VariableNode ? $value->name->value : null;
    }

    /**
     * @param list<FieldNode> $fields
     * @param array<array-key, array|string|int|float|bool|null> $variables
     * @param list<string> $keys
     */
    public function findArgumentVariableValue(
        array $fields,
        array $variables,
        array $keys
    ): ?string {
        $variableName = $this->findArgumentVariableName($fields, $keys);
        if ($variableName === null) {
            return null;
        }

        $value = $variables[$variableName] ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param list<FieldNode> $fields
     * @param array<array-key, array|string|int|float|bool|null> $variables
     * @param list<string> $keys
     */
    public function findInputObjectVariableValue(
        array $fields,
        array $variables,
        array $keys
    ): ?string {
        foreach ($this->inputVariableNames($fields) as $variableName) {
            $resolved = $this->resolveInputVariableStringValue($variables, $variableName, $keys);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * @param list<FieldNode> $fields
     * @param array<array-key, array|string|int|float|bool|null> $variables
     * @param list<string> $keys
     *
     * @return list<string>
     */
    public function findStringValuesForFields(
        array $fields,
        array $variables,
        array $keys
    ): array {
        $values = [];
        foreach ($fields as $field) {
            array_push($values, ...$this->findStringValuesForField($field, $variables, $keys));
        }

        return $values;
    }

    /**
     * @param list<FieldNode> $fields
     *
     * @return list<string>
     */
    public function inputVariableNames(array $fields): array
    {
        $variableNames = [];
        foreach ($fields as $field) {
            array_push($variableNames, ...$this->inputVariableNamesForField($field));
        }

        return $this->uniqueStringValues($variableNames);
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null> $variables
     * @param list<string> $keys
     *
     * @return list<string>
     */
    private function findStringValuesForField(
        FieldNode $field,
        array $variables,
        array $keys
    ): array {
        $values = [];
        $value = $this->findNamedFieldArgumentValue($field, $keys);
        $resolved = $this->resolveStringValue($value, $variables);
        if ($resolved !== null) {
            $values[] = $resolved;
        }

        foreach ($this->inputVariableNamesForField($field) as $variableName) {
            $resolved = $this->resolveInputVariableStringValue($variables, $variableName, $keys);
            if ($resolved !== null) {
                $values[] = $resolved;
            }
        }

        return $values;
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null> $variables
     */
    private function resolveStringValue(
        ?ValueNode $value,
        array $variables
    ): ?string {
        if ($value instanceof StringValueNode) {
            return $this->resolveInlineStringValue($value);
        }

        if ($value instanceof VariableNode) {
            return $this->resolveVariableStringValue($value, $variables);
        }

        return null;
    }

    private function resolveInlineStringValue(StringValueNode $value): ?string
    {
        return $value->value !== '' ? $value->value : null;
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null> $variables
     */
    private function resolveVariableStringValue(
        VariableNode $value,
        array $variables
    ): ?string {
        $resolved = $variables[$value->name->value] ?? null;

        return is_string($resolved) && $resolved !== '' ? $resolved : null;
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null> $variables
     * @param list<string> $keys
     */
    private function resolveInputVariableStringValue(
        array $variables,
        string $variableName,
        array $keys
    ): ?string {
        $value = $variables[$variableName] ?? null;
        if (!is_array($value)) {
            return null;
        }

        return $this->nestedPayloadStringResolver->resolve($value, $keys);
    }

    /**
     * @param list<string> $values
     *
     * @return list<string>
     */
    private function uniqueStringValues(array $values): array
    {
        return array_values(array_unique($values));
    }

    /**
     * @return list<string>
     */
    private function inputVariableNamesForField(FieldNode $field): array
    {
        $variableNames = [];
        foreach ($field->arguments as $argument) {
            if ($argument->name->value !== 'input') {
                continue;
            }

            if ($argument->value instanceof VariableNode) {
                $variableNames[] = $argument->value->name->value;
            }
        }

        return $variableNames;
    }

    /**
     * @param list<FieldNode> $fields
     * @param list<string> $keys
     */
    private function findNamedInputValue(array $fields, array $keys): ?ValueNode
    {
        foreach ($fields as $field) {
            $value = $this->findNamedFieldArgumentValue($field, $keys);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param list<string> $keys
     */
    private function findNamedFieldArgumentValue(FieldNode $field, array $keys): ?ValueNode
    {
        foreach ($field->arguments as $argument) {
            if (in_array($argument->name->value, $keys, true)) {
                return $argument->value;
            }

            $nested = $this->findNamedObjectFieldValue($argument->value, $keys);
            if ($nested !== null) {
                return $nested;
            }
        }

        return null;
    }

    /**
     * @param list<string> $keys
     */
    private function findNamedObjectFieldValue(ValueNode $value, array $keys): ?ValueNode
    {
        if (!$value instanceof ObjectValueNode) {
            return null;
        }

        foreach ($value->fields as $field) {
            if (in_array($field->name->value, $keys, true)) {
                return $field->value;
            }

            $nested = $this->findNamedObjectFieldValue($field->value, $keys);
            if ($nested !== null) {
                return $nested;
            }
        }

        return null;
    }
}
