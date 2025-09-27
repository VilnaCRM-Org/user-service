<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

final class ArrayContextBuilder
{
    /**
     * @param array<Parameter> $params
     */
    public function build(array $params): \ArrayObject
    {
        if ($params === []) {
            return $this->buildEmptyContent();
        }

        $items = [];
        $example = [];
        $required = [];

        foreach ($params as $param) {
            if ($param->required) {
                $required[] = $param->name;
            }

            $items[$param->name] = $this->createPropertySchema($param);
            $example[$param->name] = $param->example;
        }

        return $this->buildContent($items, $example, $required);
    }

    private function buildEmptyContent(): \ArrayObject
    {
        return new \ArrayObject([
            'application/json' => [
                'example' => [],
                'schema' => [
                    'type' => 'array',
                    'items' => ['type' => 'object'],
                ],
            ],
        ]);
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
    ): \ArrayObject {
        return new \ArrayObject([
            'application/json' => [
                'schema' => [
                    'type' => 'array',
                    'items' => array_filter(
                        [
                            'type' => 'object',
                            'properties' => $items,
                            'required' => $required === [] ? null : $required,
                        ],
                        static fn ($value) => $value !== null
                    ),
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
}
