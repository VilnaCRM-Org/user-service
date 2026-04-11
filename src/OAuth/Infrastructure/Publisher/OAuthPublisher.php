<?php

declare(strict_types=1);

namespace App\OAuth\Infrastructure\Publisher;

use App\OAuth\Domain\Factory\Event\OAuthEventFactoryInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Factory\EventIdFactoryInterface;

/**
 * @psalm-api
 */
final readonly class OAuthPublisher implements OAuthPublisherInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
        private EventIdFactoryInterface $eventIdFactory,
        private OAuthEventFactoryInterface $oAuthEventFactory,
    ) {
    }

    #[\Override]
    public function publishUserCreated(
        string $userId,
        string $email,
        string $provider
    ): void {
        $this->eventBus->publish($this->oAuthEventFactory->createUserCreated(
            $userId,
            $email,
            $provider,
            $this->eventIdFactory->generate()
        ));
    }

    #[\Override]
    public function publishUserSignedIn(
        string $userId,
        string $email,
        string $provider,
        string $sessionId
    ): void {
        $this->eventBus->publish($this->oAuthEventFactory->createUserSignedIn(
            $userId,
            $email,
            $provider,
            $sessionId,
            $this->eventIdFactory->generate()
        ));
    }
}
