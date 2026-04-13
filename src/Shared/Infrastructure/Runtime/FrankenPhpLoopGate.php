<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime;

final class FrankenPhpLoopGate
{
    private int $handledRequests = 0;

    public function __construct(private readonly int $loopMax)
    {
    }

    public function keepRunning(bool $handled): bool
    {
        if (!$handled) {
            return false;
        }

        if ($this->isUnlimited()) {
            return true;
        }

        return ++$this->handledRequests < $this->loopMax;
    }

    private function isUnlimited(): bool
    {
        return $this->loopMax < 0;
    }
}
