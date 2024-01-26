<?php

declare(strict_types=1);

namespace App\Shared\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;

interface AbstractResponseFactory
{
    public function getResponse(): Response;
}
