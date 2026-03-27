<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Event;

interface DomainEventSubscriberInterface
{
    /**
     * @return array<class-string<DomainEvent>>
     */
    public function subscribedTo(): array;
}
