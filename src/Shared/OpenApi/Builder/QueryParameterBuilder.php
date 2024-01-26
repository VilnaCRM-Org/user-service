<?php

declare(strict_types=1);

namespace App\Shared\OpenApi\Builder;

use ApiPlatform\OpenApi\Model\Parameter;

final class QueryParameterBuilder
{
    public function build(
        string $name,
        string $description,
        bool $required,
        string $example,
    ): Parameter {
        return new Parameter(
            name: $name,
            in: 'query',
            description: $description,
            required: $required,
            example: $example,
        );
    }
}
