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
        $event = $this->createSignedInEvent();
        $capturedContext = [];
        $this->expectLogCapture('info', 'User signed in successfully', $capturedContext);

        $this->subscriber->__invoke($event);

        $this->assertSignedInContext($capturedContext, $event);
    }

    public function testInvokeLogsSignInFailedAtWarningLevel(): void
    {
        $event = new SignInFailedEvent(
            $this->faker->email(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            'invalid_credentials',
            $this->faker->uuid()
        );
        $capturedContext = [];
        $this->expectLogCapture('warning', 'Sign-in attempt failed', $capturedContext);

        $this->subscriber->__invoke($event);

        $this->assertFailedContext($capturedContext, $event);
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

    private function createSignedInEvent(): UserSignedInEvent
    {
        return new UserSignedInEvent(
            $this->faker->uuid(),
            $this->faker->email(),
            $this->faker->uuid(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            false,
            $this->faker->uuid()
        );
    }

    /**
     * @param array<string, string|bool> $capturedContext
     */
    private function expectLogCapture(
        string $method,
        string $message,
        array &$capturedContext
    ): void {
        $this->logger->expects($this->once())
            ->method($method)
            ->with(
                $message,
                $this->callback(static function (array $context) use (&$capturedContext): bool {
                    $capturedContext = $context;
                    return true;
                })
            );
    }

    /**
     * @param array<string, string|bool> $context
     */
    private function assertSignedInContext(
        array $context,
        UserSignedInEvent $event
    ): void {
        $this->assertSame('user.signed_in', $context['event']);
        $this->assertSame($event->userId, $context['userId']);
        $this->assertSame($event->sessionId, $context['sessionId']);
        $this->assertSame($event->ipAddress, $context['ip']);
        $this->assertSame($event->userAgent, $context['userAgent']);
        $this->assertSame($event->twoFactorUsed, $context['twoFactorUsed']);
        $this->assertArrayHasKey('timestamp', $context);
    }

    /**
     * @param array<string, string> $context
     */
    private function assertFailedContext(
        array $context,
        SignInFailedEvent $event
    ): void {
        $this->assertSame('user.signin.failed', $context['event']);
        $this->assertSame($event->email, $context['attemptedEmail']);
        $this->assertSame($event->ipAddress, $context['ip']);
        $this->assertSame($event->userAgent, $context['userAgent']);
        $this->assertSame($event->reason, $context['reason']);
        $this->assertArrayHasKey('timestamp', $context);
    }
}
