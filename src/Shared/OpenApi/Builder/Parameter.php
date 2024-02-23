<?php

declare(strict_types=1);

namespace App\Shared\OpenApi\Builder;

final readonly class Parameter
{
    public function __construct(
        public string $name,
        public string $type,
        public string|int|array|bool $example
    ) {
    }
}
