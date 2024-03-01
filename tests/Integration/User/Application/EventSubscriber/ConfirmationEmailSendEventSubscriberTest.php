<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Application\EventSubscriber;

use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Integration\TestEmailSendingUtils;
use App\User\Application\EventSubscriber\ConfirmationEmailSentEventSubscriber;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Event\ConfirmationEmailSentEvent;

class ConfirmationEmailSendEventSubscriberTest extends IntegrationTestCase
{
    private ConfirmationEmailSentEventSubscriber $subscriber;
    private TestEmailSendingUtils $utils;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriber = $this->container->get(
            ConfirmationEmailSentEventSubscriber::class
        );
        $this->utils = new TestEmailSendingUtils();
    }

    public function testConfirmationEmailSent(): void
    {
        $tokenValue = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $emailAddress = $this->faker->email();
        $token = new ConfirmationToken($tokenValue, $userId);
        $event = new ConfirmationEmailSentEvent(
            $token,
            $emailAddress,
            $this->faker->uuid()
        );

        $this->subscriber->__invoke($event);
        $this->utils->assertEmailWasSent($this->container, $emailAddress);
    }
}
