<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory\Event;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Event\EmailChangedEvent;
use App\User\Domain\Factory\Event\EmailChangedEventFactory;
use App\User\Domain\Factory\Event\EmailChangedEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;

final class EmailChangedEventFactoryTest extends UnitTestCase
{
    private EmailChangedEventFactoryInterface $factory;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new EmailChangedEventFactory();
        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer(new UuidFactory());
    }

    public function testCreateEvent(): void
    {
        $email = $this->faker->email();
        $oldEmail = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userId = $this->faker->uuid();

        $user = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->transformer->transformFromString($userId)
        );

        $eventId = $this->faker->uuid();

        $event = $this->factory->create($user, $oldEmail, $eventId);

        $this->assertInstanceOf(EmailChangedEvent::class, $event);
        $this->assertEquals($user, $event->user);
        $this->assertEquals($oldEmail, $event->oldEmail);
    }
}
