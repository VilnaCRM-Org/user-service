<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\ValueObject\Header;
use App\Shared\Application\OpenApi\ValueObject\Parameter;
use ArrayObject;

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
        array $headers,
        string $contentType = 'application/json'
    ): Response {
        return new Response(
            description: $description,
            content: $this->contextBuilder->build($params, $contentType),
            headers: $this->buildHeaders($headers)
        );
    }

    /**
     * @param array<Header> $headers
     */
    private function buildHeaders(array $headers): ArrayObject
    {
        $headersArray = new ArrayObject();

        foreach ($headers as $header) {
            $schema = array_filter(
                [
                    'type' => $header->type,
                    'format' => $header->format,
                ],
                static fn ($value): bool => $value !== null
            );

            $headersArray[$header->name] = array_filter(
                [
                    'description' => $header->description,
                    'schema' => $schema,
                    'example' => $header->example,
                ],
                static fn ($value): bool => $value !== null
            );
        }

        return $headersArray;
    }
}
