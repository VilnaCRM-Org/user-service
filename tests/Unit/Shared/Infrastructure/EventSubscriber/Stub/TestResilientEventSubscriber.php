<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventSubscriber\Stub;

use App\Shared\Infrastructure\EventSubscriber\ResilientEventSubscriber;

final readonly class TestResilientEventSubscriber extends ResilientEventSubscriber
{
    /**
     * @psalm-return array<never, never>
     */
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [];
    }

    public function testSafeExecute(callable $handler, string $eventName): void
    {
        $this->safeExecute($handler, $eventName);
    }
}
