<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory\Event;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\UserRegisteredEvent;
use App\User\Domain\Factory\Event\UserRegisteredEventFactory;
use App\User\Domain\Factory\Event\UserRegisteredEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;

final class UserRegisteredEventFactoryTest extends UnitTestCase
{
    private UserRegisteredEventFactoryInterface $factory;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new UserRegisteredEventFactory();
        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer();
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

        $eventId = $this->faker->uuid();

        $event = $this->factory->create($user, $eventId);

        $this->assertInstanceOf(UserRegisteredEvent::class, $event);
        $this->assertEquals($user, $event->user);
    }
}
