<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\RequestFactory;

use ApiPlatform\OpenApi\Model\RequestBody;

interface AbstractRequestFactory
{
    public function getRequest(): RequestBody;
}
