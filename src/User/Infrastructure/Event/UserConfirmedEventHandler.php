<?php

namespace App\User\Infrastructure\Event;

use App\Shared\Domain\Bus\Event\DomainEventSubscriber;
use App\User\Domain\TokenRepositoryInterface;

class UserConfirmedEventHandler implements DomainEventSubscriber
{
    public function __construct(private TokenRepositoryInterface $tokenRepository)
    {
    }

    public static function subscribedTo(): array
    {
        return [UserConfirmedEvent::class];
    }

    public function __invoke(UserConfirmedEvent $userConfirmedEvent)
    {
        $this->tokenRepository->delete($userConfirmedEvent->token);
    }
}
