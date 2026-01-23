<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\PasswordResetRequestedMetricsSubscriber;
use App\User\Application\Factory\PasswordResetRequestsMetricFactory;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\PasswordResetRequestedEvent;

final class PasswordResetRequestedMetricsSubscriberTest extends UnitTestCase
{
    private BusinessMetricsEmitterSpy $metricsEmitterSpy;
    private PasswordResetRequestedMetricsSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->metricsEmitterSpy = new BusinessMetricsEmitterSpy();
        $this->subscriber = new PasswordResetRequestedMetricsSubscriber(
            $this->metricsEmitterSpy,
            new PasswordResetRequestsMetricFactory()
        );
    }

    public function testSubscribedToReturnsCorrectEvents(): void
    {
        self::assertSame([PasswordResetRequestedEvent::class], $this->subscriber->subscribedTo());
    }

    public function testInvokeEmitsPasswordResetMetric(): void
    {
        $user = new User(
            email: $this->faker->email(),
            initials: 'JD',
            password: 'secret',
            id: new Uuid((string) $this->faker->uuid())
        );

        ($this->subscriber)(new PasswordResetRequestedEvent(
            userId: $user->getId(),
            userEmail: $user->getEmail(),
            token: $this->faker->uuid(),
            eventId: (string) $this->faker->uuid()
        ));

        self::assertSame(1, $this->metricsEmitterSpy->count());
    }

    public function testThrowsWhenEmitterFails(): void
    {
        $user = $this->createTestUser();
        $event = $this->createPasswordResetRequestedEvent($user);

        $failingEmitter = $this->createMock(BusinessMetricsEmitterInterface::class);
        $failingEmitter
            ->method('emit')
            ->willThrowException(new \RuntimeException('Connection failed'));

        $subscriber = new PasswordResetRequestedMetricsSubscriber(
            $failingEmitter,
            new PasswordResetRequestsMetricFactory()
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection failed');

        ($subscriber)($event);
    }

    private function createTestUser(): User
    {
        return new User(
            email: $this->faker->email(),
            initials: 'JD',
            password: 'secret',
            id: new Uuid((string) $this->faker->uuid())
        );
    }

    private function createPasswordResetRequestedEvent(User $user): PasswordResetRequestedEvent
    {
        return new PasswordResetRequestedEvent(
            userId: $user->getId(),
            userEmail: $user->getEmail(),
            token: $this->faker->uuid(),
            eventId: (string) $this->faker->uuid()
        );
    }
}
