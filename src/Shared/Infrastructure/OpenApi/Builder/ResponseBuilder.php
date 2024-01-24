<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Builder;

use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\Model;

class ResponseBuilder
{
    public function __construct(private ContextBuilder $contextBuilder)
    {
    }

    /**
     * @param string $description
     * @param array<Parameter> $params
     * @param array<Header> $headers
     * @return Response
     */
    public function build(
        string $description,
        array $params = null,
        array $headers = null
    ): Response {
        $content = $this->contextBuilder->build($params);
        $headersArray = new \ArrayObject();

        if ($headers) {
            foreach ($headers as $header) {
                $headersArray[$header->name] = new Model\Header(
                    description: $header->description,
                    schema: [
                        'type' => $header->type,
                        'format' => $header->format,
                        'example' => $header->example
                    ]
                );
            }
        }

        return new Response(
            description: $description,
            content: $content,
            headers: $headersArray
        );
    }
}
