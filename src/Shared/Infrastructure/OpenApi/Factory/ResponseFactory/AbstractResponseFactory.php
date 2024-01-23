<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory;

use ApiPlatform\OpenApi\Model\Response;

interface AbstractResponseFactory
{
    public function getResponse(): Response;
}
