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
     * @return (int|string|string[])[]
     *
     * @psalm-return array<'items'|'minItems'|'type', 'array'|array{type: 'object'|'string'}|int>
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
     * @return string[]
     *
     * @psalm-return array{type: 'object'|'string'}
     */
    private function resolveItemsSchema(array|bool|int|string|null $example): array
    {
        $firstValue = $this->exampleValueExtractor
            ->extract($example);

        if (!is_array($firstValue)) {
            return ['type' => 'string'];
        }

        return ['type' => 'object'];
    }
}
