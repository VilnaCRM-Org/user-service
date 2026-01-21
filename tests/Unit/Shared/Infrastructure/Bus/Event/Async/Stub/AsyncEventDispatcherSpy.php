<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async\Stub;

use App\Shared\Application\Bus\Event\AsyncEventDispatcherInterface;
use App\Shared\Domain\Bus\Event\DomainEvent;

final class AsyncEventDispatcherSpy implements AsyncEventDispatcherInterface
{
    /** @var array<DomainEvent> */
    private array $dispatched = [];
    private bool $shouldFail = false;

    public function dispatch(DomainEvent ...$events): bool
    {
        if ($this->shouldFail) {
            return false;
        }

        foreach ($events as $event) {
            $this->dispatched[] = $event;
        }

        return true;
    }

    public function failNextDispatch(): void
    {
        $this->shouldFail = true;
    }

    /**
     * @return array<DomainEvent>
     */
    public function dispatched(): array
    {
        return $this->dispatched;
    }

    public function clear(): void
    {
        $this->dispatched = [];
        $this->shouldFail = false;
    }
}
