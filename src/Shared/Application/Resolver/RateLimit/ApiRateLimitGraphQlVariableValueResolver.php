<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\RateLimit;

use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Language\AST\VariableNode;

final readonly class ApiRateLimitGraphQlVariableValueResolver
{
    private ApiRateLimitNestedPayloadStringResolver $nestedPayloadStringResolver;

    public function __construct()
    {
        $this->nestedPayloadStringResolver = new ApiRateLimitNestedPayloadStringResolver();
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null> $variables
     * @param array<string, ValueNode> $variableDefaultValues
     */
    public function resolveStringValue(
        ?ValueNode $value,
        array $variables,
        array $variableDefaultValues
    ): ?string {
        if ($value instanceof StringValueNode) {
            return $this->resolveInlineStringValue($value);
        }

        if ($value instanceof VariableNode) {
            return $this->resolveVariableStringValue($value, $variables, $variableDefaultValues);
        }

        return null;
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null> $variables
     * @param array<string, ValueNode> $variableDefaultValues
     */
    public function resolveVariableNameStringValue(
        string $variableName,
        array $variables,
        array $variableDefaultValues
    ): ?string {
        if (array_key_exists($variableName, $variables)) {
            $resolved = $variables[$variableName];

            return is_string($resolved) && $resolved !== '' ? $resolved : null;
        }

        $defaultValue = $variableDefaultValues[$variableName] ?? null;

        return $this->resolveStringValue($defaultValue, $variables, $variableDefaultValues);
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null> $variables
     * @param array<string, ValueNode> $variableDefaultValues
     * @param list<string> $keys
     */
    public function resolveInputVariableStringValue(
        array $variables,
        array $variableDefaultValues,
        string $variableName,
        array $keys
    ): ?string {
        if (!array_key_exists($variableName, $variables)) {
            return $this->resolveInputVariableDefaultStringValue(
                $variableDefaultValues,
                $variableName,
                $keys
            );
        }

        $value = $variables[$variableName];
        if (!is_array($value)) {
            return null;
        }

        return $this->nestedPayloadStringResolver->resolve($value, $keys);
    }

    private function resolveInlineStringValue(StringValueNode $value): ?string
    {
        return $value->value !== '' ? $value->value : null;
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null> $variables
     * @param array<string, ValueNode> $variableDefaultValues
     */
    private function resolveVariableStringValue(
        VariableNode $value,
        array $variables,
        array $variableDefaultValues
    ): ?string {
        return $this->resolveVariableNameStringValue(
            $value->name->value,
            $variables,
            $variableDefaultValues
        );
    }

    /**
     * @param array<string, ValueNode> $variableDefaultValues
     * @param list<string> $keys
     */
    private function resolveInputVariableDefaultStringValue(
        array $variableDefaultValues,
        string $variableName,
        array $keys
    ): ?string {
        $defaultValue = $variableDefaultValues[$variableName] ?? null;

        return $defaultValue instanceof ObjectValueNode
            ? $this->resolveStringValue(
                $this->findNamedObjectFieldValue($defaultValue, $keys),
                [],
                $variableDefaultValues
            )
            : null;
    }

    /**
     * @param list<string> $keys
     */
    private function findNamedObjectFieldValue(ValueNode $value, array $keys): ?ValueNode
    {
        if ($value instanceof ObjectValueNode) {
            foreach ($value->fields as $field) {
                if (in_array($field->name->value, $keys, true)) {
                    return $field->value;
                }

                $nested = $this->findNamedObjectFieldValue($field->value, $keys);
                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        return null;
    }
}
