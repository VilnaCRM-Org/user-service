<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\RateLimit;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\Parser;

final readonly class ApiRateLimitGraphQlDocumentResolver
{
    public function resolve(string $query): ?DocumentNode
    {
        try {
            return $this->parse($query);
        } catch (\Throwable) {
            return null;
        }
    }

    private function parse(string $query): DocumentNode
    {
        /** @var callable(string): DocumentNode $parseDocument */
        $parseDocument = [Parser::class, 'parse'];

        return $parseDocument($query);
    }
}
