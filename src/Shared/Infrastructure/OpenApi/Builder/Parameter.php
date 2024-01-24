<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Builder;

final readonly class Parameter
{
    public function __construct(
        public string $name,
        public string $type,
        public string|int|array $example
    ) {
    }
}
