<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\RateLimit;

use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Language\AST\VariableNode;

final readonly class ApiRateLimitGraphQlQueryInspection
{
    public function __construct(private OperationDefinitionNode $operation)
    {
    }

    /**
     * @param list<string> $fieldNames
     */
    public function containsMutationField(array $fieldNames): bool
    {
        if ($this->operation->operation !== 'mutation') {
            return false;
        }

        foreach ($this->operation->selectionSet->selections as $selection) {
            if (!$selection instanceof FieldNode) {
                continue;
            }

            if (in_array($selection->name->value, $fieldNames, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $keys
     */
    public function findArgumentStringValue(array $keys): ?string
    {
        $value = $this->findNamedInputValue($keys);

        return $value instanceof StringValueNode && $value->value !== '' ? $value->value : null;
    }

    /**
     * @param list<string> $keys
     */
    public function findArgumentVariableName(array $keys): ?string
    {
        $value = $this->findNamedInputValue($keys);

        return $value instanceof VariableNode ? $value->name->value : null;
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null> $variables
     * @param list<string> $keys
     */
    public function findArgumentVariableValue(array $variables, array $keys): ?string
    {
        $variableName = $this->findArgumentVariableName($keys);
        if ($variableName === null) {
            return null;
        }

        $value = $variables[$variableName] ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null> $variables
     * @param list<string> $keys
     */
    public function findInputObjectVariableValue(array $variables, array $keys): ?string
    {
        foreach ($this->inputVariableNames() as $variableName) {
            $value = $variables[$variableName] ?? null;
            if (!is_array($value)) {
                continue;
            }

            $resolved = $this->findStringValue($value, $keys);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function inputVariableNames(): array
    {
        $variableNames = [];
        foreach ($this->operation->selectionSet->selections as $selection) {
            if (!$selection instanceof FieldNode) {
                continue;
            }

            array_push($variableNames, ...$this->inputVariableNamesForField($selection));
        }

        return $variableNames;
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

            if (!$argument->value instanceof VariableNode) {
                continue;
            }

            $variableNames[] = $argument->value->name->value;
        }

        return $variableNames;
    }

    /**
     * @param list<string> $keys
     */
    private function findNamedInputValue(array $keys): ?ValueNode
    {
        foreach ($this->operation->selectionSet->selections as $selection) {
            if (!$selection instanceof FieldNode) {
                continue;
            }

            $value = $this->findNamedFieldArgumentValue($selection, $keys);
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

    /**
     * @param array<array-key, array|string|int|float|bool|null> $payload
     * @param list<string> $keys
     */
    private function findStringValue(array $payload, array $keys): ?string
    {
        foreach ($payload as $key => $value) {
            $resolved = $this->resolvePayloadEntry($key, $value, $keys);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null>|string|int|float|bool|null $value
     * @param list<string> $keys
     */
    private function resolvePayloadEntry(
        int|string $key,
        array|string|int|float|bool|null $value,
        array $keys
    ): ?string {
        if (is_string($key) && $this->isMatchingStringValue($key, $value, $keys)) {
            return $value;
        }

        return is_array($value) ? $this->findStringValue($value, $keys) : null;
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null>|string|int|float|bool|null $value
     * @param list<string> $keys
     */
    private function isMatchingStringValue(
        string $key,
        array|string|int|float|bool|null $value,
        array $keys
    ): bool {
        return in_array($key, $keys, true) && is_string($value) && $value !== '';
    }
}
