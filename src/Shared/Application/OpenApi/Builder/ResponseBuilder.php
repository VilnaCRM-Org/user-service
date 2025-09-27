<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

use ApiPlatform\OpenApi\Model\Response;

final class ResponseBuilder
{
    public function __construct(private ContextBuilder $contextBuilder)
    {
    }

    /**
     * @param array<Parameter> $params
     * @param array<Header> $headers
     */
    public function build(
        string $description,
        array $params,
        array $headers
    ): Response {
        return new Response(
            description: $description,
            content: $this->contextBuilder->build($params),
            headers: $this->buildHeaders($headers)
        );
    }

    /**
     * @param array<Header> $headers
     */
    private function buildHeaders(array $headers): \ArrayObject
    {
        $headersArray = new \ArrayObject();

        foreach ($headers as $header) {
            $schema = ['type' => $header->type];

            if ($header->format !== null) {
                $schema['format'] = $header->format;
            }

            $headerData = [
                'description' => $header->description,
                'schema' => $schema,
            ];

            if ($header->example !== null) {
                $headerData['example'] = $header->example;
            }

            $headersArray[$header->name] = $headerData;
        }

        return $headersArray;
    }
}
