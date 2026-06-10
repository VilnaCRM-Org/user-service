<?php

declare(strict_types=1);

namespace App\Tests\Behat\Support;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Domain\Event\PasswordResetRequestedEvent;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Captures the plaintext password reset token emitted while handling an HTTP
 * request. Only the hash is persisted, so the confirmation step replays the
 * plaintext recorded here instead of reading the stored hash.
 */
final class PasswordResetTokenRecorder implements
    DomainEventSubscriberInterface,
    ResetInterface
{
    private string $lastPlainToken = '';

    /** @var array<string, string> */
    private array $plainTokensByUserId = [];

    public function __invoke(PasswordResetRequestedEvent $event): void
    {
        $this->lastPlainToken = $event->token;
        $this->plainTokensByUserId[$event->userId] = $event->token;
    }

    public function getLastPlainToken(): string
    {
        return $this->lastPlainToken;
    }

    public function getPlainTokenForUser(string $userId): ?string
    {
        return $this->plainTokensByUserId[$userId] ?? null;
    }

    /**
     * @return array<string>
     *
     * @psalm-return list{PasswordResetRequestedEvent::class}
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [PasswordResetRequestedEvent::class];
    }

    #[\Override]
    public function reset(): void
    {
        $this->lastPlainToken = '';
        $this->plainTokensByUserId = [];
    }
}
