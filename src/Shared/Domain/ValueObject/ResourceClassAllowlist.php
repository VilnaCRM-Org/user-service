<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use App\Internal\HealthCheck\Domain\ValueObject\HealthCheck;
use App\User\Domain\Entity\User;

final readonly class ResourceClassAllowlist
{
    /**
     * @return array<int, class-string>
     */
    public function all(): array
    {
        return [
            User::class,
            HealthCheck::class,
        ];
    }
}
