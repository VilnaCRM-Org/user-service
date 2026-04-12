<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\OAuth\Support;

use App\OAuth\Infrastructure\Publisher\OAuthPublisherInterface;
use App\Tests\Shared\OAuth\Support\RecordingOAuthPublisher;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class RecordingOAuthPublisherTest extends UnitTestCase
{
    private OAuthPublisherInterface&MockObject $innerPublisher;
    private RecordingOAuthPublisher $publisher;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->innerPublisher = $this->createMock(OAuthPublisherInterface::class);
        $this->publisher = new RecordingOAuthPublisher($this->innerPublisher);
    }

    public function testPublishUserCreatedRecordsEventAndDelegates(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->safeEmail();
        $provider = 'github';

        $this->innerPublisher->expects($this->once())
            ->method('publishUserCreated')
            ->with($userId, $email, $provider);

        $this->publisher->publishUserCreated($userId, $email, $provider);

        $this->assertSame([[
            'userId' => $userId,
            'email' => $email,
            'provider' => $provider,
        ],
        ], $this->publisher->createdEvents());
    }

    public function testPublishUserSignedInRecordsEventAndDelegates(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->safeEmail();
        $provider = 'google';
        $sessionId = $this->faker->uuid();

        $this->innerPublisher->expects($this->once())
            ->method('publishUserSignedIn')
            ->with($userId, $email, $provider, $sessionId);

        $this->publisher->publishUserSignedIn($userId, $email, $provider, $sessionId);

        $this->assertSame([[
            'userId' => $userId,
            'email' => $email,
            'provider' => $provider,
            'sessionId' => $sessionId,
        ],
        ], $this->publisher->signedInEvents());
    }

    public function testResetClearsRecordedEvents(): void
    {
        $this->publisher->publishUserCreated(
            $this->faker->uuid(),
            $this->faker->safeEmail(),
            'facebook',
        );
        $this->publisher->publishUserSignedIn(
            $this->faker->uuid(),
            $this->faker->safeEmail(),
            'twitter',
            $this->faker->uuid(),
        );

        $this->publisher->reset();

        $this->assertSame([], $this->publisher->createdEvents());
        $this->assertSame([], $this->publisher->signedInEvents());
    }
}
