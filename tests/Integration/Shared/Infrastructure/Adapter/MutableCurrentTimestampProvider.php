<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\Infrastructure\Adapter;

use App\Shared\Application\Provider\CurrentTimestampProviderInterface;

final class MutableCurrentTimestampProvider implements CurrentTimestampProviderInterface
{
    public function __construct(
        private int $timestamp
    ) {
    }

    #[\Override]
    public function currentTimestamp(): int
    {
        return $this->timestamp;
    }

    public function advance(int $seconds): void
    {
        $this->timestamp += $seconds;
    }
}
