<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Enum;

enum AllowEmptyValue
{
    case ALLOWED;
    case DISALLOWED;

    public function toBool(): bool
    {
        return $this === self::ALLOWED;
    }
}
