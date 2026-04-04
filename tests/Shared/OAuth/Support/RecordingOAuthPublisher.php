<?php

declare(strict_types=1);

namespace App\Tests\Shared\OAuth\Support;

use App\OAuth\Infrastructure\Publisher\OAuthPublisherInterface;

final class RecordingOAuthPublisher implements OAuthPublisherInterface
{
    /** @var list<array{userId: string, email: string, provider: string}> */
    private array $createdEvents = [];

    /** @var list<array{userId: string, email: string, provider: string, sessionId: string}> */
    private array $signedInEvents = [];

    public function __construct(private readonly OAuthPublisherInterface $inner)
    {
    }

    #[\Override]
    public function publishUserCreated(
        string $userId,
        string $email,
        string $provider,
    ): void {
        $this->createdEvents[] = [
            'userId' => $userId,
            'email' => $email,
            'provider' => $provider,
        ];

        $this->inner->publishUserCreated($userId, $email, $provider);
    }

    #[\Override]
    public function publishUserSignedIn(
        string $userId,
        string $email,
        string $provider,
        string $sessionId,
    ): void {
        $this->signedInEvents[] = [
            'userId' => $userId,
            'email' => $email,
            'provider' => $provider,
            'sessionId' => $sessionId,
        ];

        $this->inner->publishUserSignedIn(
            $userId,
            $email,
            $provider,
            $sessionId,
        );
    }

    /**
     * @return list<array{userId: string, email: string, provider: string}>
     */
    public function createdEvents(): array
    {
        return $this->createdEvents;
    }

    /**
     * @return list<array{userId: string, email: string, provider: string, sessionId: string}>
     */
    public function signedInEvents(): array
    {
        return $this->signedInEvents;
    }

    public function reset(): void
    {
        $this->createdEvents = [];
        $this->signedInEvents = [];
    }
}
