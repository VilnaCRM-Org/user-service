<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Domain\Event\UserConfirmedEvent;
use App\User\Domain\Repository\TokenRepositoryInterface;

final readonly class UserConfirmedEventSubscriber implements
    DomainEventSubscriberInterface
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository
    ) {
    }

    public function __invoke(UserConfirmedEvent $userConfirmedEvent): void
    {
        $token = $this->tokenRepository->find($userConfirmedEvent->tokenValue);

        if ($token !== null) {
            $this->tokenRepository->delete($token);
        }
    }

    /**
     * @return array<DomainEvent>
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [UserConfirmedEvent::class];
    }
}
