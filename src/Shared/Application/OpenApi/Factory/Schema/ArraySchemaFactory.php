<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Schema;

use App\Shared\Application\OpenApi\Extractor\ArrayExampleValueExtractor;
use App\Shared\Application\OpenApi\ValueObject\Parameter;

final readonly class ArraySchemaFactory
{
    public function __construct(
        private ArrayExampleValueExtractor $exampleValueExtractor
    ) {
    }

    /**
     * @return array{type: string, items: array<string, string>}
     */
    public function create(Parameter $param): array
    {
        return array_filter(
            [
                'type' => 'array',
                'items' => $this->resolveItemsSchema($param->example),
                'minItems' => $param->minItems,
            ],
            static fn ($value) => $value !== null
        );
    }

    /**
     * @return array<string, string>
     */
    private function resolveItemsSchema(mixed $example): array
    {
        $firstValue = $this->exampleValueExtractor
            ->extract($example);

        if (!is_array($firstValue)) {
            return ['type' => 'string'];
        }

        return ['type' => 'object'];
    }
}
