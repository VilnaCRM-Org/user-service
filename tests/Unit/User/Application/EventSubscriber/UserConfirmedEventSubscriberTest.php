<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\UserConfirmedEventSubscriber;
use App\User\Domain\Event\UserConfirmedEvent;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\UserConfirmedEventFactory;
use App\User\Domain\Factory\Event\UserConfirmedEventFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;

final class UserConfirmedEventSubscriberTest extends UnitTestCase
{
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    private UserConfirmedEventFactoryInterface $userConfirmedEventFactory;
    private TokenRepositoryInterface $tokenRepository;
    private UserConfirmedEventSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->confirmationTokenFactory = new ConfirmationTokenFactory(
            $this->faker->numberBetween(1, 10)
        );
        $this->userConfirmedEventFactory = new UserConfirmedEventFactory();
        $this->tokenRepository =
            $this->createMock(TokenRepositoryInterface::class);
        $this->subscriber =
            new UserConfirmedEventSubscriber($this->tokenRepository);
    }

    public function testInvoke(): void
    {
        $token = $this->confirmationTokenFactory->create($this->faker->uuid());
        $event = $this->userConfirmedEventFactory->create(
            $token,
            $this->faker->uuid()
        );

        $this->tokenRepository->expects($this->once())
            ->method('delete')
            ->with($this->equalTo($token));

        $this->subscriber->__invoke($event);
    }

    public function testSubscribedTo(): void
    {
        $this->assertSame(
            [UserConfirmedEvent::class],
            $this->subscriber->subscribedTo()
        );
    }
}
