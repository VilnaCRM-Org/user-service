<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\ValueObject;

final readonly class Header
{
    public function __construct(
        public string $name,
        public string $description,
        public string $type,
        public ?string $format = null,
        public ?string $example = null
    ) {
    }
}
