<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Cleaner;

use ApiPlatform\OpenApi\Model;

final class PathParameterCleaner
{
    /**
     * @param Model\Parameter|array<string> $parameter
     *
     * @psalm-param Model\Parameter|list{'not-a-parameter'} $parameter
     */
    public function clean(array|Model\Parameter $parameter): mixed
    {
        if (!$parameter instanceof Model\Parameter) {
            return $parameter;
        }

        if ($parameter->getIn() !== 'path') {
            return $parameter;
        }

        return $parameter
            ->withAllowEmptyValue(null)
            ->withAllowReserved(null);
    }
}
