<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

interface UserCacheInvalidationSubscriberInterface extends DomainEventSubscriberInterface
{
}
