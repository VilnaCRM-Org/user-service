<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\HttpClient;

final class TestEmailSendingUtils extends KernelTestCase
{
    public function assertEmailWasSent(string $emailAddress): void
    {
        $this->waitForEmail($emailAddress);
    }

    private function waitForEmail(string $emailAddress): void
    {
        $deadline = microtime(true) + 5;
        do {
            $messages = $this->fetchMessages();

            if ($this->findEmailByAddress($messages, $emailAddress) !== null) {
                break;
            }
            usleep(250_000); // 250 ms
        } while (microtime(true) < $deadline);
    }

    /**
     * @return array<int, array<string, string|int|bool|array>>
     */
    private function fetchMessages(): array
    {
        $httpClient = HttpClient::create();
        return $httpClient->request(
            'GET',
            'http://mailer:' . getenv('MAILCATCHER_HTTP_PORT') . '/messages'
        )->toArray();
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
            if ($this->isMessageForRecipient($message, $emailAddress)) {
                return $message;
            }
        }

        return null;
    }

    /**
     * @param array<string, string|int|bool|array> $message
     */
    private function isMessageForRecipient(
        array $message,
        string $emailAddress
    ): bool {
        $hasRecipient = isset($message['recipients'][0]);
        return $hasRecipient &&
            $message['recipients'][0] === '<' . $emailAddress . '>';
    }

    /**
     * @param array<string, string|int|bool|array>|null $foundMessage
     */
    private function validateFoundEmail(
        ?array $foundMessage,
        string $emailAddress
    ): void {
        $this->assertEmailWasFound($foundMessage, $emailAddress);
        $this->validateEmailRecipient($foundMessage, $emailAddress);
        $this->validateEmailSender($foundMessage);
    }

    /**
     * @param array<string, string|int|bool|array>|null $foundMessage
     */
    private function assertEmailWasFound(
        ?array $foundMessage,
        string $emailAddress
    ): void {
        $this->assertNotNull(
            $foundMessage,
            "Email to {$emailAddress} was not found in MailCatcher"
        );
    }

    /**
     * @param array<string, string|int|bool|array> $foundMessage
     */
    private function validateEmailRecipient(
        array $foundMessage,
        string $emailAddress
    ): void {
        $this->assertEquals(
            '<' . $emailAddress . '>',
            $foundMessage['recipients'][0]
        );
    }

    /**
     * @param array<string, string|int|bool|array> $foundMessage
     */
    private function validateEmailSender(array $foundMessage): void
    {
        $this->assertEquals(
            '<' . getenv('MAIL_SENDER') . '>',
            $foundMessage['sender']
        );
    }
}
