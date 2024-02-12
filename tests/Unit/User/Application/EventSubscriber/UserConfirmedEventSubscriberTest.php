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

class UserConfirmedEventSubscriberTest extends UnitTestCase
{
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    private UserConfirmedEventFactoryInterface $userConfirmedEventFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->confirmationTokenFactory = new ConfirmationTokenFactory($this->faker->numberBetween(1, 10));
        $this->userConfirmedEventFactory = new UserConfirmedEventFactory();
    }

    public function testInvoke(): void
    {
        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);

        $subscriber = new UserConfirmedEventSubscriber($tokenRepository);

        $token = $this->confirmationTokenFactory->create($this->faker->uuid());
        $event = $this->userConfirmedEventFactory->create($token, $this->faker->uuid());

        $tokenRepository->expects($this->once())
            ->method('delete')
            ->with($this->equalTo($token));

        $subscriber->__invoke($event);
    }

    public function testSubscribedTo(): void
    {
        $this->assertSame(
            [UserConfirmedEvent::class],
            UserConfirmedEventSubscriber::subscribedTo()
        );
    }
}
