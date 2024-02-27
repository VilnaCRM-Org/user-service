<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

final class ContextBuilder
{
    /**
     * @param array<Parameter> $params
     */
    public function build(array $params): \ArrayObject
    {
        $content = new \ArrayObject([
            'application/json' => [
                'example' => '',
            ],
        ]);

        if (count($params) > 0) {
            $properties = [];
            $example = [];

            foreach ($params as $param) {
                $properties[$param->name] = ['type' => $param->type];
                $example[$param->name] = $param->example;
            }

            $content = $this->buildContent($properties, $example);
        }

        return $content;
    }

    /**
     * @param array<string, string> $properties
     * @param array<string, string|int|array> $example
     */
    private function buildContent(
        array $properties,
        array $example
    ): \ArrayObject {
        return new \ArrayObject([
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => $properties,
                ],
                'example' => $example,
            ],
        ]);
    }
}
