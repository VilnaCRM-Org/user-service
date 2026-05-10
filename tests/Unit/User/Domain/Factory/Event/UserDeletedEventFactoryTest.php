<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory\Event;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\UserDeletedEvent;
use App\User\Domain\Factory\Event\UserDeletedEventFactory;
use App\User\Domain\Factory\Event\UserDeletedEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;

final class UserDeletedEventFactoryTest extends UnitTestCase
{
    private UserDeletedEventFactoryInterface $factory;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new UserDeletedEventFactory();
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

        $eventId = $this->faker->uuid();

        $event = $this->factory->create($user, $eventId);

        $this->assertInstanceOf(UserDeletedEvent::class, $event);
        $this->assertEquals($user->getId(), $event->userId);
        $this->assertEquals($user->getEmail(), $event->email);
    }
}
