<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\UriParamFactory;

use ApiPlatform\OpenApi\Model\Parameter;

interface AbstractUriParameterFactory
{
    public function getParameter(): Parameter;
}
