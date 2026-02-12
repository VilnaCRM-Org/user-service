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

    public function testInvokeLogsRefreshTokenRotatedAtInfoLevel(): void
    {
        $sessionId = $this->faker->uuid();

        $event = new RefreshTokenRotatedEvent($sessionId);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Refresh token rotated',
                $this->callback(function ($context) use ($sessionId) {
                    return $context['event'] === 'user.refresh_token.rotated'
                        && $context['session_id'] === $sessionId
                        && $context['old_token_revoked'] === true;
                })
            );

        $this->subscriber->__invoke($event);
    }

    public function testInvokeLogsRefreshTokenTheftAtCriticalLevel(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $ipAddress = $this->faker->ipv4();

        $event = new RefreshTokenTheftDetectedEvent($sessionId, $userId, $ipAddress, "double_grace_use", $this->faker->uuid());

        $this->logger->expects($this->once())
            ->method('critical')
            ->with(
                'Refresh token theft detected',
                $this->callback(function ($context) use ($sessionId, $userId, $ipAddress) {
                    return $context['event'] === 'user.refresh_token.theft_detected'
                        && $context['session_id'] === $sessionId
                        && $context['user_id'] === $userId
                        && $context['ip_address'] === $ipAddress;
                })
            );

        $this->subscriber->__invoke($event);
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
}
