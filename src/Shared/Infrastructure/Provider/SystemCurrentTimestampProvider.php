<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Provider;

use App\Shared\Application\Provider\CurrentTimestampProviderInterface;

final readonly class SystemCurrentTimestampProvider implements CurrentTimestampProviderInterface
{
    #[\Override]
    public function currentTimestamp(): int
    {
        return time();
    }
}
