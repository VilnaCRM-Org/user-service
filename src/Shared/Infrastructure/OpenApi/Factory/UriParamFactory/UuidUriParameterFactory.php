<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\UriParamFactory;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Shared\Infrastructure\OpenApi\Builder\UriParameterBuilder;

final class UuidUriParameterFactory implements AbstractUriParameterFactory
{
    public function __construct(private UriParameterBuilder $parameterBuilder)
    {
    }

    public function getParameter(): Parameter
    {
        return $this->parameterBuilder->build(
            'id',
            'User identifier',
            true,
            '2b10b7a3-67f0-40ea-a367-44263321592a'
        );
    }
}
