<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\RateLimit;

use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\SelectionSetNode;

final readonly class ApiRateLimitGraphQlRootFields
{
    /**
     * @param array<string, FragmentDefinitionNode> $fragments
     */
    public function __construct(
        private OperationDefinitionNode $operation,
        private array $fragments,
    ) {
    }

    /**
     * @return list<FieldNode>
     */
    public function all(): array
    {
        return $this->collectFromSelectionSet($this->operation->selectionSet, []);
    }

    /**
     * @param list<string> $fieldNames
     *
     * @return list<FieldNode>
     */
    public function matchingMutationFields(array $fieldNames): array
    {
        if ($this->operation->operation !== 'mutation') {
            return [];
        }

        $matchingFields = [];
        foreach ($this->all() as $field) {
            if (in_array($field->name->value, $fieldNames, true)) {
                $matchingFields[] = $field;
            }
        }

        return $matchingFields;
    }

    /**
     * @param array<string, string> $visitedFragmentNames
     *
     * @return list<FieldNode>
     */
    private function collectFromSelectionSet(
        SelectionSetNode $selectionSet,
        array $visitedFragmentNames
    ): array {
        $fields = [];
        foreach ($selectionSet->selections as $selection) {
            array_push(
                $fields,
                ...$this->resolveSelectionFields($selection, $visitedFragmentNames)
            );
        }

        return $fields;
    }

    /**
     * @param array<string, string> $visitedFragmentNames
     *
     * @return list<FieldNode>
     */
    private function resolveSelectionFields(
        mixed $selection,
        array $visitedFragmentNames
    ): array {
        if ($selection instanceof FieldNode) {
            return [$selection];
        }

        if ($selection instanceof InlineFragmentNode) {
            return $this->collectFromSelectionSet($selection->selectionSet, $visitedFragmentNames);
        }

        return $selection instanceof FragmentSpreadNode
            ? $this->resolveFragmentFields($selection, $visitedFragmentNames)
            : [];
    }

    /**
     * @param array<string, string> $visitedFragmentNames
     *
     * @return list<FieldNode>
     */
    private function resolveFragmentFields(
        FragmentSpreadNode $selection,
        array $visitedFragmentNames
    ): array {
        $fragmentName = $selection->name->value;
        if (isset($visitedFragmentNames[$fragmentName])) {
            return [];
        }

        $fragment = $this->fragments[$fragmentName] ?? null;
        if (!$fragment instanceof FragmentDefinitionNode) {
            return [];
        }

        return $this->collectFromSelectionSet(
            $fragment->selectionSet,
            $visitedFragmentNames + [$fragmentName => $fragmentName]
        );
    }
}
