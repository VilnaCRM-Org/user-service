<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\UserRegisteredMetricsSubscriber;
use App\User\Application\Factory\UsersRegisteredMetricFactory;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\UserRegisteredEvent;

final class UserRegisteredMetricsSubscriberTest extends UnitTestCase
{
    private BusinessMetricsEmitterSpy $metricsEmitterSpy;
    private UserRegisteredMetricsSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metricsEmitterSpy = new BusinessMetricsEmitterSpy();
        $this->subscriber = new UserRegisteredMetricsSubscriber(
            $this->metricsEmitterSpy,
            new UsersRegisteredMetricFactory()
        );
    }

    public function testSubscribedToReturnsCorrectEvents(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        self::assertCount(1, $subscribedEvents);
        self::assertContains(UserRegisteredEvent::class, $subscribedEvents);
    }

    public function testInvokeEmitsUsersRegisteredMetric(): void
    {
        $user = new User(
            email: $this->faker->email(),
            initials: 'JD',
            password: 'secret',
            id: new Uuid((string) $this->faker->uuid())
        );

        $event = new UserRegisteredEvent($user, (string) $this->faker->uuid());

        ($this->subscriber)($event);

        self::assertSame(1, $this->metricsEmitterSpy->count());

        foreach ($this->metricsEmitterSpy->emitted() as $metric) {
            self::assertSame('UsersRegistered', $metric->name());
            self::assertSame(1, $metric->value());
            self::assertSame('User', $metric->dimensions()->values()->get('Endpoint'));
            self::assertSame('create', $metric->dimensions()->values()->get('Operation'));
        }
    }

    public function testThrowsWhenEmitterFails(): void
    {
        $user = new User(
            email: $this->faker->email(),
            initials: 'JD',
            password: 'secret',
            id: new Uuid((string) $this->faker->uuid())
        );

        $event = new UserRegisteredEvent($user, (string) $this->faker->uuid());

        $failingEmitter = $this->createMock(BusinessMetricsEmitterInterface::class);
        $failingEmitter
            ->method('emit')
            ->willThrowException(new \RuntimeException('Connection failed'));

        $subscriber = new UserRegisteredMetricsSubscriber(
            $failingEmitter,
            new UsersRegisteredMetricFactory()
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection failed');

        ($subscriber)($event);
    }
}
