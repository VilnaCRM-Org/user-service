<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\RequestFactory;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Infrastructure\OpenApi\Builder\RequestBuilder;

final class EmptyRequestFactory implements AbstractRequestFactory
{
    public function __construct(private RequestBuilder $requestBuilder)
    {
    }

    public function getRequest(): RequestBody
    {
        return $this->requestBuilder->build();
    }
}
