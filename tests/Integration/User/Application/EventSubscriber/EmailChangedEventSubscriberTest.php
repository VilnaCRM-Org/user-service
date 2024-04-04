<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Application\EventSubscriber;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Integration\TestEmailSendingUtils;
use App\User\Application\EventSubscriber\EmailChangedEventSubscriber;
use App\User\Domain\Event\EmailChangedEvent;
use App\User\Domain\Factory\UserFactoryInterface;

final class EmailChangedEventSubscriberTest extends IntegrationTestCase
{
    private EmailChangedEventSubscriber $subscriber;
    private TestEmailSendingUtils $utils;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriber = $this->container->get(
            EmailChangedEventSubscriber::class
        );
        $this->utils = new TestEmailSendingUtils();
    }

    public function testConfirmationEmailSent(): void
    {
        $userId = $this->faker->uuid();
        $emailAddress = $this->faker->email();
        $user = $this->container->get(UserFactoryInterface::class)->create(
            $emailAddress,
            $this->faker->name(),
            $this->faker->password(),
            $this->container->get(
                UuidTransformer::class
            )->transformFromString($userId)
        );
        $event = new EmailChangedEvent(
            $user,
            $this->faker->uuid()
        );

        $this->subscriber->__invoke($event);
        $this->utils->assertEmailWasSent($this->container, $emailAddress);
    }
}
