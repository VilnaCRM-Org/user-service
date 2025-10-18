<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

use function array_filter;
use function array_values;
use ArrayObject;

final class ArrayContextBuilder
{
    private const EMPTY_CONTENT = [
        'application/json' => [
            'example' => [],
            'schema' => [
                'type' => 'array',
                'items' => ['type' => 'object'],
            ],
        ],
    ];

    /**
     * @param array<Parameter> $params
     */
    public function build(array $params): ArrayObject
    {
        return $params === []
            ? new ArrayObject(self::EMPTY_CONTENT)
            : $this->buildForParams($params);
    }

    /**
     * @param array<string, array<string, string|int>> $items
     * @param array<string, string|int|array|bool> $example
     * @param array<int, string> $required
     */
    private function buildContent(
        array $items,
        array $example,
        array $required
    ): ArrayObject {
        $itemSchema = [
            'type' => 'object',
            'properties' => $items,
        ];

        if ($required !== []) {
            $itemSchema['required'] = $required;
        }

        return new ArrayObject([
            'application/json' => [
                'schema' => [
                    'type' => 'array',
                    'items' => $itemSchema,
                ],
                'example' => [$example],
            ],
        ]);
    }

    /**
     * @return array<string, string|int>
     */
    private function createPropertySchema(Parameter $param): array
    {
        return array_filter(
            [
                'type' => $param->type,
                'maxLength' => $param->maxLength,
                'format' => $param->format,
            ],
            static fn ($value) => $value !== null
        );
    }

    /**
     * @param array<Parameter> $params
     */
    private function buildForParams(array $params): ArrayObject
    {
        $items = [];
        $example = [];
        $required = [];

        foreach ($params as $param) {
            $items[$param->name] = $this->createPropertySchema($param);
            $example[$param->name] = $param->example;

            if ($param->isRequired()) {
                $required[$param->name] = $param->name;
            }
        }

        return $this->buildContent($items, $example, array_values($required));
    }
}
