<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Application\EventSubscriber;

use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Integration\TestEmailSendingUtils;
use App\User\Application\EventSubscriber\ConfirmationEmailSentEventSubscriber;
use App\User\Domain\Event\ConfirmationEmailSentEvent;

final class ConfirmationEmailSendEventSubscriberTest extends IntegrationTestCase
{
    private ConfirmationEmailSentEventSubscriber $subscriber;
    private TestEmailSendingUtils $emailUtils;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriber = $this->container->get(
            ConfirmationEmailSentEventSubscriber::class
        );
        $this->emailUtils = new TestEmailSendingUtils($this->container);
    }

    public function testConfirmationEmailSent(): void
    {
        $tokenValue = $this->faker->uuid();
        $emailAddress = $this->faker->email();
        $event = new ConfirmationEmailSentEvent(
            $tokenValue,
            $emailAddress,
            $this->faker->uuid()
        );

        $this->subscriber->__invoke($event);
        $this->emailUtils->assertEmailWasSent($this->getMailerEvent(), $emailAddress);
    }
}
