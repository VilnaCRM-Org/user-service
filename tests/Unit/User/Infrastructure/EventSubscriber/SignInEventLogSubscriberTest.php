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

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'User signed in successfully',
                $this->callback(function ($context) use ($userId, $sessionId, $ip, $userAgent, $twoFactorUsed) {
                    return $context['event'] === 'user.signed_in'
                        && $context['user_id'] === $userId
                        && $context['session_id'] === $sessionId
                        && $context['ip_address'] === $ip
                        && $context['user_agent'] === $userAgent
                        && $context['two_factor_used'] === $twoFactorUsed
                        && isset($context['timestamp']);
                })
            );

        $this->subscriber->__invoke($event);
    }

    public function testInvokeLogsSignInFailedAtWarningLevel(): void
    {
        $email = $this->faker->email();
        $ip = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $reason = 'invalid_credentials';
        $eventId = $this->faker->uuid();

        $event = new SignInFailedEvent($email, $ip, $userAgent, $reason, $eventId);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Sign-in attempt failed',
                $this->callback(function ($context) use ($email, $ip, $reason) {
                    return $context['event'] === 'user.signin.failed'
                        && $context['attempted_email'] === $email
                        && $context['ip_address'] === $ip
                        && $context['reason'] === $reason
                        && isset($context['timestamp']);
                })
            );

        $this->subscriber->__invoke($event);
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
