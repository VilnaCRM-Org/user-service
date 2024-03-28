<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Application\EventSubscriber;

use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Integration\TestEmailSendingUtils;
use App\User\Application\EventSubscriber\PasswordChangedEventSubscriber;
use App\User\Domain\Event\PasswordChangedEvent;

class PasswordChangedEventSubscriberTest extends IntegrationTestCase
{
    private PasswordChangedEventSubscriber $subscriber;
    private TestEmailSendingUtils $utils;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriber = $this->container->get(
            PasswordChangedEventSubscriber::class
        );
        $this->utils = new TestEmailSendingUtils();
    }

    public function testConfirmationEmailSent(): void
    {
        $emailAddress = $this->faker->email();
        $event = new PasswordChangedEvent(
            $emailAddress,
            $this->faker->uuid()
        );

        $this->subscriber->__invoke($event);
        $this->utils->assertEmailWasSent($this->container, $emailAddress);
    }
}
