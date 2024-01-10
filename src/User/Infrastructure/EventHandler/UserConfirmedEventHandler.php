<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventHandler;

use App\Shared\Domain\Bus\Event\DomainEventSubscriber;
use App\User\Domain\Event\UserConfirmedEvent;
use App\User\Domain\TokenRepositoryInterface;

class UserConfirmedEventHandler implements DomainEventSubscriber
{
    public function __construct(private TokenRepositoryInterface $tokenRepository)
    {
    }

    public function __invoke(UserConfirmedEvent $userConfirmedEvent): void
    {
        $this->tokenRepository->delete($userConfirmedEvent->token);
    }

    public static function subscribedTo(): array
    {
        return [UserConfirmedEvent::class];
    }
}
