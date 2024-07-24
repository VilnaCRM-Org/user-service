<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory\Event;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\PasswordResetRequestedEvent;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\PasswordResetRequestedEventFactory;
use App\User\Domain\Factory\Event\PasswordResetRequestedEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;

final class PasswordResetRequestedEventFactoryTest extends UnitTestCase
{
    private PasswordResetRequestedEventFactoryInterface $factory;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new PasswordResetRequestedEventFactory();
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer();
        $this->confirmationTokenFactory = new ConfirmationTokenFactory(
            $this->faker->numberBetween(1, 10)
        );
    }

    public function testCreateEvent(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userId = $this->faker->uuid();

        $user = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->uuidTransformer->transformFromString($userId)
        );
        $token = $this->confirmationTokenFactory->create($userId);
        $eventId = $this->faker->uuid();

        $event = $this->factory->create($token, $user, $eventId);

        $this->assertInstanceOf(
            PasswordResetRequestedEvent::class,
            $event
        );
        $this->assertEquals($token, $event->token);
        $this->assertEquals($user->getEmail(), $event->emailAddress);
        $this->assertEquals($eventId, $event->eventId());
    }
}
