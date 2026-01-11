<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Cleaner;

use ApiPlatform\OpenApi\Model;

final class PathParameterCleaner
{
    public function clean(mixed $parameter): mixed
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
