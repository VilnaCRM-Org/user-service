<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Builder;

use ApiPlatform\OpenApi\Model\Response;

class ResponseBuilder
{
    /**
     * @param string $description
     * @param array<ResponseParameter> $params
     * @return Response
     */
    public function build(string $description, array $params = null): Response
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

        return new Response(
            description: $description,
            content: $content
        );
    }
}
