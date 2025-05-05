<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Application\EventSubscriber;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Integration\TestEmailSendingUtils;
use App\User\Application\EventSubscriber\UserRegisteredEventSubscriber;
use App\User\Domain\Event\UserRegisteredEvent;
use App\User\Domain\Factory\UserFactoryInterface;

final class UserRegisteredEventSubscriberTest extends IntegrationTestCase
{
    private UserRegisteredEventSubscriber $subscriber;
    private TestEmailSendingUtils $utils;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriber = $this->container->get(
            UserRegisteredEventSubscriber::class
        );
        $this->utils = new TestEmailSendingUtils();
    }

    public function testConfirmationEmailSent(): void
    {
        $userId = $this->faker->uuid();
        $emailAddress = 'test@example.com';
        $user = $this->container->get(UserFactoryInterface::class)->create(
            $emailAddress,
            $this->faker->name(),
            $this->faker->password(),
            $this->container->get(
                UuidTransformer::class
            )->transformFromString($userId)
        );
        $event = new UserRegisteredEvent(
            $user,
            $this->faker->uuid()
        );

        $this->subscriber->__invoke($event);
        $this->utils->assertEmailWasSent($this->container, $emailAddress);
    }
}
