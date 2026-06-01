<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\RateLimit;

use GraphQL\Language\AST\ObjectValueNode;
use GraphQL\Language\AST\ValueNode;

final readonly class ApiRateLimitGraphQlObjectFieldResolver
{
    /**
     * @param list<string> $keys
     */
    public function findNested(ValueNode $value, array $keys): ?ValueNode
    {
        if (!$value instanceof ObjectValueNode) {
            return null;
        }

        foreach ($value->fields as $field) {
            if (in_array($field->name->value, $keys, true)) {
                return $field->value;
            }

            $nested = $this->findNested($field->value, $keys);
            if ($nested !== null) {
                return $nested;
            }
        }

        return null;
    }

    /**
     * @param list<string> $keys
     */
    public function findDirect(ObjectValueNode $value, array $keys): ?ValueNode
    {
        foreach ($value->fields as $field) {
            if (in_array($field->name->value, $keys, true)) {
                return $field->value;
            }
        }

        return null;
    }
}
