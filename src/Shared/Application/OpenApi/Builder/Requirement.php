<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

enum Requirement
{
    case REQUIRED;
    case OPTIONAL;

    public function toBool(): bool
    {
        return $this === self::REQUIRED;
    }
}
