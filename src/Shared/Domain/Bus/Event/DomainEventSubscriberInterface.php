<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Event;

interface DomainEventSubscriberInterface
{
    /**
     * @return array<DomainEvent>
     */
    public static function subscribedTo(): array;
}
