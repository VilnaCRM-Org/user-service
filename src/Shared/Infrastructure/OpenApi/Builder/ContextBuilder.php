<?php

namespace App\Shared\Infrastructure\OpenApi\Builder;

class ContextBuilder
{
    public function build(array $params = null)
    {
        $content = new \ArrayObject([
            'application/json' => [
                'example' => '',
            ],
        ]);

        if ($params) {
            $properties = [];
            $example = [];

            foreach ($params as $param) {
                $properties[$param->name] = ['type' => $param->type];
                $example[$param->name] = $param->example;
            }

            $content = new \ArrayObject([
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => $properties,
                    ],
                    'example' => $example,
                ],
            ]);
        }

        return $content;
    }
}
