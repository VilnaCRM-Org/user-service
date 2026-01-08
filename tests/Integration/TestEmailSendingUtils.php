<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use PHPUnit\Framework\Assert;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;

final readonly class TestEmailSendingUtils
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    public function assertEmailWasSent(MessageEvent $mailerEvent, string $emailAddress): void
    {
        $message = new SendEmailMessage(
            $mailerEvent->getMessage(),
            $mailerEvent->getEnvelope()
        );
        $this->container->get('mailer.messenger.message_handler')->__invoke($message);

        $httpClient = HttpClient::create();
        $response = $httpClient->request(
            'GET',
            'http://mailer:'.getenv('MAILCATCHER_HTTP_PORT').'/messages'
        )->toArray();
        $message = $response[sizeof($response) - 1];

        Assert::assertEquals(
            '<' . $emailAddress . '>',
            $message['recipients'][0]
        );
        Assert::assertEquals(
            '<'.getenv('MAIL_SENDER').'>',
            $message['sender']
        );
    }
}
