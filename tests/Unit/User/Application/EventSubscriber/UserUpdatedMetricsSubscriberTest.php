<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\UserUpdatedMetricsSubscriber;
use App\User\Application\Factory\UsersUpdatedMetricFactory;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\EmailChangedEvent;
use App\User\Domain\Event\PasswordChangedEvent;

final class UserUpdatedMetricsSubscriberTest extends UnitTestCase
{
    private BusinessMetricsEmitterSpy $metricsEmitterSpy;
    private UserUpdatedMetricsSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->metricsEmitterSpy = new BusinessMetricsEmitterSpy();
        $this->subscriber = new UserUpdatedMetricsSubscriber(
            $this->metricsEmitterSpy,
            new UsersUpdatedMetricFactory()
        );
    }

    public function testSubscribedToReturnsBothEvents(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        self::assertSame([
            EmailChangedEvent::class,
            PasswordChangedEvent::class,
        ], $subscribedEvents);
    }

    public function testInvokeEmitsMetricForEmailChange(): void
    {
        $user = new User(
            email: $this->faker->email(),
            initials: 'JD',
            password: 'secret',
            id: new Uuid((string) $this->faker->uuid())
        );

        ($this->subscriber)(new EmailChangedEvent(
            userId: $user->getId(),
            newEmail: $this->faker->email(),
            oldEmail: $user->getEmail(),
            eventId: (string) $this->faker->uuid()
        ));

        self::assertSame(1, $this->metricsEmitterSpy->count());
    }

    public function testInvokeEmitsMetricForPasswordChange(): void
    {
        ($this->subscriber)(new PasswordChangedEvent(
            $this->faker->email(),
            (string) $this->faker->uuid()
        ));

        self::assertSame(1, $this->metricsEmitterSpy->count());
    }

    public function testThrowsWhenEmitterFails(): void
    {
        $user = new User(
            email: $this->faker->email(),
            initials: 'JD',
            password: 'secret',
            id: new Uuid((string) $this->faker->uuid())
        );

        $event = new EmailChangedEvent(
            userId: $user->getId(),
            newEmail: $this->faker->email(),
            oldEmail: $user->getEmail(),
            eventId: (string) $this->faker->uuid()
        );

        $failingEmitter = $this->createMock(BusinessMetricsEmitterInterface::class);
        $failingEmitter
            ->method('emit')
            ->willThrowException(new \RuntimeException('Connection failed'));

        $subscriber = new UserUpdatedMetricsSubscriber(
            $failingEmitter,
            new UsersUpdatedMetricFactory()
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection failed');

        ($subscriber)($event);
    }
}
