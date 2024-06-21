<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\OpenApi;

interface AbstractEndpointFactory
{
    public function createEndpoint(OpenApi $openApi): void;
}
