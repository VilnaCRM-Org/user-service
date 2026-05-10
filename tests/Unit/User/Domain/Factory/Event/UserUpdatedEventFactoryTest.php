<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory\Event;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\UserUpdatedEvent;
use App\User\Domain\Factory\Event\UserUpdatedEventFactory;
use App\User\Domain\Factory\Event\UserUpdatedEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;

final class UserUpdatedEventFactoryTest extends UnitTestCase
{
    private UserUpdatedEventFactoryInterface $factory;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new UserUpdatedEventFactory();
        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer(new UuidFactory());
    }

    public function testCreateEvent(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();

        $user = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->transformer->transformFromString($userId)
        );

        $previousEmail = $this->faker->email();
        $eventId = $this->faker->uuid();

        $event = $this->factory->create($user, $previousEmail, $eventId);

        $this->assertInstanceOf(UserUpdatedEvent::class, $event);
        $this->assertEquals($user->getId(), $event->userId);
        $this->assertEquals($user->getEmail(), $event->email);
        $this->assertEquals($previousEmail, $event->previousEmail);
    }
}
