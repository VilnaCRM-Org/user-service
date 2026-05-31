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
    private ApiRateLimitGraphQlVariableValueResolver $variableValueResolver;

    public function __construct()
    {
        $this->variableValueResolver = new ApiRateLimitGraphQlVariableValueResolver();
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
     * @param array<string, ValueNode> $variableDefaultValues
     * @param list<string> $keys
     */
    public function findArgumentVariableValue(
        array $fields,
        array $variables,
        array $variableDefaultValues,
        array $keys
    ): ?string {
        $variableName = $this->findArgumentVariableName($fields, $keys);
        if ($variableName === null) {
            return null;
        }

        return $this->variableValueResolver->resolveVariableNameStringValue(
            $variableName,
            $variables,
            $variableDefaultValues
        );
    }

    /**
     * @param list<FieldNode> $fields
     * @param array<array-key, array|string|int|float|bool|null> $variables
     * @param array<string, ValueNode> $variableDefaultValues
     * @param list<string> $keys
     */
    public function findInputObjectVariableValue(
        array $fields,
        array $variables,
        array $variableDefaultValues,
        array $keys
    ): ?string {
        foreach ($this->inputVariableNames($fields) as $variableName) {
            $resolved = $this->variableValueResolver->resolveInputVariableStringValue(
                $variables,
                $variableDefaultValues,
                $variableName,
                $keys
            );
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * @param list<FieldNode> $fields
     * @param array<array-key, array|string|int|float|bool|null> $variables
     * @param array<string, ValueNode> $variableDefaultValues
     * @param list<string> $keys
     *
     * @return list<string>
     */
    public function findStringValuesForFields(
        array $fields,
        array $variables,
        array $variableDefaultValues,
        array $keys
    ): array {
        $values = [];
        foreach ($fields as $field) {
            array_push(
                $values,
                ...$this->findStringValuesForField(
                    $field,
                    $variables,
                    $variableDefaultValues,
                    $keys
                )
            );
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
     * @param array<string, ValueNode> $variableDefaultValues
     * @param list<string> $keys
     *
     * @return list<string>
     */
    private function findStringValuesForField(
        FieldNode $field,
        array $variables,
        array $variableDefaultValues,
        array $keys
    ): array {
        $value = $this->findNamedFieldArgumentValue($field, $keys);
        $resolved = $this->variableValueResolver
            ->resolveStringValue($value, $variables, $variableDefaultValues);
        $values = $resolved === null ? [] : [$resolved];

        foreach ($this->inputVariableNamesForField($field) as $variableName) {
            $resolved = $this->resolveInputVariableStringValue(
                $variables,
                $variableDefaultValues,
                $variableName,
                $keys
            );
            if ($resolved !== null) {
                $values[] = $resolved;
            }
        }

        return $values;
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null> $variables
     * @param array<string, ValueNode> $variableDefaultValues
     * @param list<string> $keys
     */
    private function resolveInputVariableStringValue(
        array $variables,
        array $variableDefaultValues,
        string $variableName,
        array $keys
    ): ?string {
        return $this->variableValueResolver->resolveInputVariableStringValue(
            $variables,
            $variableDefaultValues,
            $variableName,
            $keys
        );
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
