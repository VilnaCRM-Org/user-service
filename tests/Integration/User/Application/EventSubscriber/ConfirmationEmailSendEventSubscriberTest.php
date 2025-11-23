<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Application\EventSubscriber;

use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Integration\TestEmailSendingUtils;
use App\User\Application\EventSubscriber\ConfirmationEmailSentEventSubscriber;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Event\ConfirmationEmailSentEvent;

final class ConfirmationEmailSendEventSubscriberTest extends IntegrationTestCase
{
    private ConfirmationEmailSentEventSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriber = $this->container->get(
            ConfirmationEmailSentEventSubscriber::class
        );
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
        TestEmailSendingUtils::assertEmailWasSent($this->container, $emailAddress);
    }
}
