<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

use ApiPlatform\OpenApi\Model\RequestBody;

final class ArrayRequestBuilder
{
    public function __construct(private ArrayContextBuilder $contextBuilder)
    {
    }

    /**
     * @param array<Parameter> $params
     */
    public function build(array $params, bool $required = true): RequestBody
    {
        $content = $this->contextBuilder->build($params);

        return new RequestBody(
            content: $content,
            required: $required
        );
    }
}
