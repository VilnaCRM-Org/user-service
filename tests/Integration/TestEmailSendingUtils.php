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

    public function assertEmailWasSent(
        MessageEvent $mailerEvent,
        string $emailAddress
    ): void {
        $this->dispatchEmail($mailerEvent);
        $response = $this->fetchMailcatcherMessages();
        $expectedRecipient = '<' . $emailAddress . '>';
        $matchedMessage = $this->findByRecipient(
            $response,
            $expectedRecipient
        );

        Assert::assertNotNull(
            $matchedMessage,
            sprintf('No email found for %s', $expectedRecipient)
        );
        Assert::assertEquals(
            $expectedRecipient,
            $matchedMessage['recipients'][0]
        );
        Assert::assertEquals(
            '<'.getenv('MAIL_SENDER').'>',
            $matchedMessage['sender']
        );
    }

    private function dispatchEmail(MessageEvent $mailerEvent): void
    {
        $message = new SendEmailMessage(
            $mailerEvent->getMessage(),
            $mailerEvent->getEnvelope()
        );
        $handler = $this->container
            ->get('mailer.messenger.message_handler');
        $handler->__invoke($message);
    }

    /**
     * @return array<int, array<string, list<string>|string>>
     */
    private function fetchMailcatcherMessages(): array
    {
        $httpClient = HttpClient::create();
        $port = getenv('MAILCATCHER_HTTP_PORT');

        return $httpClient->request(
            'GET',
            'http://mailer:' . $port . '/messages'
        )->toArray();
    }

    /**
     * @param array<int, array<string, list<string>|string>> $messages
     *
     * @return array<string, list<string>|string>|null
     */
    private function findByRecipient(
        array $messages,
        string $expectedRecipient
    ): ?array {
        foreach (array_reverse($messages) as $candidate) {
            if ($this->matchesRecipient($candidate, $expectedRecipient)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param array<string, list<string>|string> $message
     */
    private function matchesRecipient(
        array $message,
        string $expectedRecipient
    ): bool {
        return isset($message['recipients'][0])
            && $message['recipients'][0] === $expectedRecipient;
    }
}
