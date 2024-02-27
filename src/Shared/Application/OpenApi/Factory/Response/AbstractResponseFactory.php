<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;

interface AbstractResponseFactory
{
    public function getResponse(): Response;
}
