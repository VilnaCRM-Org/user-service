<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\EventSubscriber;

use App\OAuth\Domain\Event\OAuthUserCreatedEvent;
use App\OAuth\Domain\Event\OAuthUserSignedInEvent;
use App\OAuth\Infrastructure\EventSubscriber\OAuthEventLogSubscriber;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class OAuthEventLogSubscriberTest extends UnitTestCase
{
    private LoggerInterface&MockObject $logger;
    private OAuthEventLogSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->subscriber = new OAuthEventLogSubscriber($this->logger);
    }

    public function testInvokeLogsUserCreatedAtInfoLevel(): void
    {
        $event = new OAuthUserCreatedEvent(
            $this->faker->uuid(),
            $this->faker->safeEmail(),
            'github',
            $this->faker->uuid(),
        );
        $capturedContext = [];

        $this->expectInfoLog('OAuth user created', $capturedContext);

        $this->subscriber->__invoke($event);

        $this->assertSame('oauth.user_created', $capturedContext['event']);
        $this->assertSame($event->userId, $capturedContext['userId']);
        $this->assertSame($event->email, $capturedContext['email']);
        $this->assertSame($event->provider, $capturedContext['provider']);
        $this->assertArrayHasKey('timestamp', $capturedContext);
    }

    public function testInvokeLogsUserSignedInAtInfoLevel(): void
    {
        $event = new OAuthUserSignedInEvent(
            $this->faker->uuid(),
            $this->faker->safeEmail(),
            'google',
            $this->faker->uuid(),
            $this->faker->uuid(),
        );
        $capturedContext = [];

        $this->expectInfoLog('OAuth user signed in', $capturedContext);

        $this->subscriber->__invoke($event);

        $this->assertSame('oauth.user_signed_in', $capturedContext['event']);
        $this->assertSame($event->userId, $capturedContext['userId']);
        $this->assertSame($event->email, $capturedContext['email']);
        $this->assertSame($event->provider, $capturedContext['provider']);
        $this->assertSame($event->sessionId, $capturedContext['sessionId']);
        $this->assertArrayHasKey('timestamp', $capturedContext);
    }

    public function testInvokeIgnoresUnknownEvent(): void
    {
        $this->logger->expects($this->never())->method('info');

        $this->subscriber->__invoke(new \stdClass());
    }

    public function testSubscribedToReturnsExpectedEvents(): void
    {
        $this->assertSame(
            [
                OAuthUserCreatedEvent::class,
                OAuthUserSignedInEvent::class,
            ],
            $this->subscriber->subscribedTo(),
        );
    }

    /**
     * @param array<string, string> $capturedContext
     */
    private function expectInfoLog(
        string $message,
        array &$capturedContext,
    ): void {
        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                $message,
                $this->callback(static function (array $context) use (&$capturedContext): bool {
                    $capturedContext = $context;

                    return true;
                }),
            );
    }
}
