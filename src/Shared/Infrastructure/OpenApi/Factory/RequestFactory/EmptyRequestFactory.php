<?php

namespace App\Shared\Infrastructure\OpenApi\Factory\RequestFactory;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Infrastructure\OpenApi\Builder\RequestBuilder;

class EmptyRequestFactory implements AbstractRequestFactory
{
    public function __construct(private RequestBuilder $requestBuilder)
    {
    }

    public function getRequest(): RequestBody
    {
        return $this->requestBuilder->build();
    }
}
