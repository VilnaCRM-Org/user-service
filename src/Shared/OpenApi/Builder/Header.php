<?php

declare(strict_types=1);

namespace App\Shared\OpenApi\Builder;

final readonly class Header
{
    public function __construct(
        public string $name,
        public string $description,
        public string $type,
        public string $format,
        public string $example
    ) {
    }
}
