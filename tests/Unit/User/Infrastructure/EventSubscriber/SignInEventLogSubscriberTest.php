<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\EventSubscriber;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\SignInFailedEvent;
use App\User\Domain\Event\UserSignedInEvent;
use App\User\Infrastructure\EventSubscriber\SignInEventLogSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class SignInEventLogSubscriberTest extends UnitTestCase
{
    private LoggerInterface&MockObject $logger;
    private SignInEventLogSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->subscriber = new SignInEventLogSubscriber($this->logger);
    }

    public function testInvokeLogsUserSignedInAtInfoLevel(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $sessionId = $this->faker->uuid();
        $ip = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $twoFactorUsed = false;
        $eventId = $this->faker->uuid();

        $event = new UserSignedInEvent($userId, $email, $sessionId, $ip, $userAgent, $twoFactorUsed, $eventId);

        $capturedContext = [];
        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'User signed in successfully',
                $this->callback(static function (array $context) use (&$capturedContext): bool {
                    $capturedContext = $context;
                    return true;
                })
            );

        $this->subscriber->__invoke($event);

        $this->assertSame('user.signed_in', $capturedContext['event']);
        $this->assertSame($userId, $capturedContext['user_id']);
        $this->assertSame($sessionId, $capturedContext['session_id']);
        $this->assertSame($ip, $capturedContext['ip_address']);
        $this->assertSame($userAgent, $capturedContext['user_agent']);
        $this->assertSame($twoFactorUsed, $capturedContext['two_factor_used']);
        $this->assertArrayHasKey('timestamp', $capturedContext);
    }

    public function testInvokeLogsSignInFailedAtWarningLevel(): void
    {
        $email = $this->faker->email();
        $ip = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $reason = 'invalid_credentials';
        $eventId = $this->faker->uuid();

        $event = new SignInFailedEvent($email, $ip, $userAgent, $reason, $eventId);

        $capturedContext = [];
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Sign-in attempt failed',
                $this->callback(static function (array $context) use (&$capturedContext): bool {
                    $capturedContext = $context;
                    return true;
                })
            );

        $this->subscriber->__invoke($event);

        $this->assertSame('user.signin.failed', $capturedContext['event']);
        $this->assertSame($email, $capturedContext['attempted_email']);
        $this->assertSame($ip, $capturedContext['ip_address']);
        $this->assertSame($reason, $capturedContext['reason']);
        $this->assertArrayHasKey('timestamp', $capturedContext);
    }

    public function testInvokeIgnoresUnknownEvent(): void
    {
        $this->logger->expects($this->never())->method('info');
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->never())->method('critical');

        $this->subscriber->__invoke(new \stdClass());
    }

    public function testSubscribedToReturnsCorrectEvents(): void
    {
        $this->assertSame(
            [
                UserSignedInEvent::class,
                SignInFailedEvent::class,
            ],
            $this->subscriber->subscribedTo()
        );
    }
}
