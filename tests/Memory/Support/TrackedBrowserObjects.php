<?php

declare(strict_types=1);

namespace App\Tests\Memory\Support;

use ShipMonk\MemoryScanner\ObjectDeallocationChecker;

final readonly class TrackedBrowserObjects
{
    public function __construct(
        private object $request,
        private object $response,
        private string $labelPrefix,
    ) {
    }

    public function expectDeallocation(ObjectDeallocationChecker $checker): void
    {
        $checker->expectDeallocation(
            $this->request,
            sprintf('%s request', $this->labelPrefix),
        );
        $checker->expectDeallocation(
            $this->response,
            sprintf('%s response', $this->labelPrefix),
        );
    }
}
