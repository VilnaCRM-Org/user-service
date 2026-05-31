<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\RateLimit;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\Parser;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
final readonly class ApiRateLimitGraphQlQueryInspector
{
    public function inspect(
        string $query,
        ?string $operationName
    ): ?ApiRateLimitGraphQlQueryInspection {
        $document = $this->parseDocument($query);

        return $document === null
            ? null
            : $this->findOperationInspection($document, $operationName);
    }

    private function parseDocument(string $query): ?DocumentNode
    {
        try {
            return Parser::parse($query);
        } catch (\Throwable) {
            return null;
        }
    }

    private function findOperationInspection(
        DocumentNode $document,
        ?string $operationName
    ): ?ApiRateLimitGraphQlQueryInspection {
        $fragments = $this->collectFragments($document);
        foreach ($document->definitions as $definition) {
            if (!$definition instanceof OperationDefinitionNode) {
                continue;
            }

            if ($this->operationMatches($definition, $operationName)) {
                return new ApiRateLimitGraphQlQueryInspection($definition, $fragments);
            }
        }

        return null;
    }

    /**
     * @return array<string, FragmentDefinitionNode>
     */
    private function collectFragments(DocumentNode $document): array
    {
        $fragments = [];
        foreach ($document->definitions as $definition) {
            if ($definition instanceof FragmentDefinitionNode) {
                $fragments[$definition->name->value] = $definition;
            }
        }

        return $fragments;
    }

    private function operationMatches(
        OperationDefinitionNode $operation,
        ?string $operationName
    ): bool {
        if ($operationName === null || $operationName === '') {
            return true;
        }

        if ($operation->name === null) {
            return false;
        }

        return $operation->name->value === $operationName;
    }
}
