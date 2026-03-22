<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Provider;

/**
 * Provides timestamps for EMF payloads
 */
interface EmfTimestampProvider
{
    /**
     * Returns current timestamp in milliseconds
     */
    public function currentTimestamp(): int;
}
