<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Builder;

use ApiPlatform\OpenApi\Model\RequestBody;

final class RequestBuilder
{
    public function __construct(private ContextBuilder $contextBuilder)
    {
    }

    /**
     * @param array<Parameter> $params
     */
    public function build(array $params = null): RequestBody
    {
        $content = $this->contextBuilder->build($params);

        return new RequestBody(
            content: $content
        );
    }
}
