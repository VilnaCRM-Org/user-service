<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener\QueryParameter;

final readonly class QueryParameterViolation
{
    public function __construct(
        public string $title,
        public string $detail
    ) {
    }
}
