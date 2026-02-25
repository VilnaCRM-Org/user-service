<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\EventSubscriber;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\RefreshTokenRotatedEvent;
use App\User\Domain\Event\RefreshTokenTheftDetectedEvent;
use App\User\Infrastructure\EventSubscriber\RefreshTokenEventLogSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class RefreshTokenEventLogSubscriberTest extends UnitTestCase
{
    private LoggerInterface&MockObject $logger;
    private RefreshTokenEventLogSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->subscriber = new RefreshTokenEventLogSubscriber($this->logger);
    }

    public function testInvokeLogsRefreshTokenRotatedAtDebugLevel(): void
    {
        $sessionId = $this->faker->uuid();
        $event = new RefreshTokenRotatedEvent(
            $sessionId,
            $this->faker->uuid(),
            $this->faker->uuid()
        );
        $capturedContext = [];
        $this->expectLogCall('debug', 'Refresh token rotated', $capturedContext);
        $this->subscriber->__invoke($event);
        $this->assertSame('user.refresh_token.rotated', $capturedContext['event']);
        $this->assertSame($sessionId, $capturedContext['sessionId']);
        $this->assertTrue($capturedContext['oldTokenRevoked']);
        $this->assertArrayHasKey('timestamp', $capturedContext);
    }

    public function testInvokeLogsRefreshTokenTheftAtCriticalLevel(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $ipAddress = $this->faker->ipv4();
        $event = new RefreshTokenTheftDetectedEvent(
            $sessionId,
            $userId,
            $ipAddress,
            'double_grace_use',
            $this->faker->uuid()
        );
        $capturedContext = [];
        $this->expectLogCall('critical', 'Refresh token theft detected', $capturedContext);
        $this->subscriber->__invoke($event);
        $this->assertTheftEventContext($capturedContext, $sessionId, $userId, $ipAddress);
    }

    public function testInvokeIgnoresUnknownEvent(): void
    {
        $this->logger->expects($this->never())->method('debug');
        $this->logger->expects($this->never())->method('info');
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->never())->method('critical');

        $this->subscriber->__invoke(new \stdClass());
    }

    public function testSubscribedToReturnsCorrectEvents(): void
    {
        $this->assertSame(
            [
                RefreshTokenRotatedEvent::class,
                RefreshTokenTheftDetectedEvent::class,
            ],
            $this->subscriber->subscribedTo()
        );
    }

    /**
     * @param array<string, string|bool> $context
     */
    private function expectLogCall(string $method, string $message, array &$context): void
    {
        $this->logger->expects($this->once())
            ->method($method)
            ->with(
                $message,
                $this->callback(static function (array $ctx) use (&$context): bool {
                    $context = $ctx;
                    return true;
                })
            );
    }

    /**
     * @param array<string, string|bool> $context
     */
    private function assertTheftEventContext(
        array $context,
        string $sessionId,
        string $userId,
        string $ipAddress
    ): void {
        $this->assertSame('user.refresh_token.theft_detected', $context['event']);
        $this->assertSame($sessionId, $context['sessionId']);
        $this->assertSame($userId, $context['userId']);
        $this->assertSame($ipAddress, $context['ip']);
        $this->assertArrayHasKey('timestamp', $context);
    }
}
