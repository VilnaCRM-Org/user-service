<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Application\EventSubscriber;

use App\Tests\Integration\IntegrationTestCase;
use App\User\Application\EventSubscriber\ConfirmationEmailSendEventSubscriber;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Event\ConfirmationEmailSendEvent;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Infrastructure\Factory\EmailFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfirmationEmailSendEventSubscriberTest extends IntegrationTestCase
{
    use MailerAssertionsTrait;
    private ConfirmationEmailSendEventSubscriber $subscriber;
    private TranslatorInterface $translator;
    private MailerInterface $mailer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailer = $this->container->get(MailerInterface::class);
        $tokenRepository = $this->container->get(TokenRepositoryInterface::class);
        $logger = $this->container->get(LoggerInterface::class);
        $this->translator = $this->container->get(TranslatorInterface::class);
        $emailFactory = $this->container->get(EmailFactoryInterface::class);

        $this->subscriber = new ConfirmationEmailSendEventSubscriber(
            $this->mailer,
            $tokenRepository,
            $logger,
            $this->translator,
            $emailFactory
        );
    }

    public function testConfirmationEmailSent(): void
    {
        $tokenValue = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $emailAddress = $this->faker->email();
        $token = new ConfirmationToken($tokenValue, $userId);
        $event = new ConfirmationEmailSendEvent(
            $token,
            $emailAddress,
            $this->faker->uuid()
        );

        $this->subscriber->__invoke($event);

        $sendMessage = self::getMailerMessage();
        self::assertQueuedEmailCount(1);
        self::assertEmailTextBodyContains($sendMessage, $tokenValue);
    }
}
