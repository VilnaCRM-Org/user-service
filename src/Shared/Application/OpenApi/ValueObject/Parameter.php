<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\ValueObject;

use App\Shared\Application\OpenApi\Enum\Requirement;

final readonly class Parameter
{
    public function __construct(
        public string $name,
        public string $type,
        public string|int|array|bool|null $example,
        public ?int $maxLength = null,
        public ?string $format = null,
        public Requirement $requirement = Requirement::REQUIRED,
        public ?string $pattern = null,
        public ?int $minItems = null,
        public ?array $enum = null
    ) {
    }

    public function isRequired(): bool
    {
        return $this->requirement->toBool();
    }
}
