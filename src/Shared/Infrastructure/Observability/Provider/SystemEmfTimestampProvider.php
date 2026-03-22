<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Provider;

/**
 * System-based timestamp provider using microtime
 */
final readonly class SystemEmfTimestampProvider implements EmfTimestampProvider
{
    #[\Override]
    public function currentTimestamp(): int
    {
        return (int) (microtime(true) * 1000);
    }
}
