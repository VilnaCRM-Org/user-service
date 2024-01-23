<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\EndpointFactory;

use ApiPlatform\OpenApi\OpenApi;

interface AbstractEndpointFactory
{
    public function createEndpoint(OpenApi $openApi): void;
}
