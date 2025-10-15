<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

use ApiPlatform\OpenApi\Model\Parameter;

final class QueryParameterBuilder
{
    public function build(
        string $name,
        string $description,
        Requirement $requirement,
        ?string $example,
        string $type,
        ?int $minLength = null,
        AllowEmptyValue $allowEmptyValue = AllowEmptyValue::DISALLOWED,
        ?array $enum = null
    ): Parameter {
        $schema = array_filter(
            [
                'type' => $type,
                'minLength' => $minLength,
                'enum' => $enum,
            ],
            static fn ($value) => $value !== null
        );

        return new Parameter(
            name: $name,
            in: 'query',
            description: $description,
            required: $requirement->toBool(),
            allowEmptyValue: $allowEmptyValue->toBool(),
            schema: $schema,
            example: $example
        );
    }
}
