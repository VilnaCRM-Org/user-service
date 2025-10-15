<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\UriParameter;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Shared\Application\OpenApi\Builder\UriParameterBuilder;

final class UuidUriParameterFactory implements AbstractUriParameterFactory
{
    private const DEFAULT_ID = '018dd6ba-e901-7a8c-b27d-65d122caca6b';

    public function __construct(private UriParameterBuilder $parameterBuilder)
    {
    }

    public function getParameter(): Parameter
    {
        return $this->getParameterFor(self::DEFAULT_ID);
    }

    public function getParameterFor(string $id): Parameter
    {
        return $this->parameterBuilder->build(
            'id',
            'User identifier',
            true,
            $id,
            'string',
            'uuid',
            [$id]
        );
    }
}
