<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;

final class TestEmailSendingUtils extends KernelTestCase
{
    public static function assertEmailWasSent(
        ContainerInterface $container,
        string $emailAddress
    ): void {
        $mailerEvent = self::getMailerEvent();
        $message = new SendEmailMessage(
            $mailerEvent->getMessage(),
            $mailerEvent->getEnvelope()
        );
        $container->get('mailer.messenger.message_handler')->__invoke($message);

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
