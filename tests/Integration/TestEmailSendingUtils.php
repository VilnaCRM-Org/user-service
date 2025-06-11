<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpClient\HttpClient;

final class TestEmailSendingUtils extends KernelTestCase
{
    public function assertEmailWasSent(
        ContainerInterface $container,
        string $emailAddress
    ): void {
        // Додаємо затримку для асинхронної обробки
        sleep(2);

        $httpClient = HttpClient::create();
        $response = $httpClient->request(
            'GET',
            'http://mailer:'.getenv('MAILCATCHER_HTTP_PORT').'/messages'
        )->toArray();

        $foundMessage = $this->findEmailByAddress($response, $emailAddress);
        $this->validateFoundEmail($foundMessage, $emailAddress);
    }

    /**
     * @param array<int, array<string, string|int|bool|array>> $messages
     *
     * @return array<string, string|int|bool|array>|null
     */
    private function findEmailByAddress(
        array $messages,
        string $emailAddress
    ): ?array {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            $message = $messages[$i];
            if (isset($message['recipients'][0]) &&
                $message['recipients'][0] === '<' . $emailAddress . '>') {
                return $message;
            }
        }

        return null;
    }

    /**
     * @param array<string, string|int|bool|array>|null $foundMessage
     */
    private function validateFoundEmail(
        ?array $foundMessage,
        string $emailAddress
    ): void {
        $this->assertNotNull(
            $foundMessage,
            "Email to {$emailAddress} was not found in MailCatcher"
        );

        $this->assertEquals(
            '<' . $emailAddress . '>',
            $foundMessage['recipients'][0]
        );
        $this->assertEquals(
            '<'.getenv('MAIL_SENDER').'>',
            $foundMessage['sender']
        );
    }
}
