<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\EventDispatcher\Stub;

use App\Shared\Infrastructure\EventDispatcher\ResilientEventSubscriber;

final readonly class TestResilientEventSubscriber extends ResilientEventSubscriber
{
    /**
     * @return array
     *
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
