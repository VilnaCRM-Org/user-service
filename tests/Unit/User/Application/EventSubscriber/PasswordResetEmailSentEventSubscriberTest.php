<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\PasswordResetEmailSentEventSubscriber;
use App\User\Application\Factory\EmailFactoryInterface;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Event\PasswordResetEmailSentEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PasswordResetEmailSentEventSubscriberTest extends UnitTestCase
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private TranslatorInterface $translator;
    private EmailFactoryInterface $emailFactory;
    private PasswordResetEmailSentEventSubscriber $subscriber;
    private string $apiBaseUrl;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->mailer = $this->createMock(MailerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->emailFactory = $this->createMock(EmailFactoryInterface::class);
        $this->apiBaseUrl = 'https://example.com';

        $this->subscriber = new PasswordResetEmailSentEventSubscriber(
            $this->mailer,
            $this->logger,
            $this->translator,
            $this->emailFactory,
            $this->apiBaseUrl
        );
    }

    public function testInvokeSuccessfully(): void
    {
        $tokenValue = $this->faker->sha256();
        $emailAddress = $this->faker->safeEmail();
        $eventId = $this->faker->uuid();

        $token = $this->createPasswordResetTokenMock($tokenValue);
        $event = new PasswordResetEmailSentEvent($token, $emailAddress, $eventId);
        $email = $this->createMock(Email::class);

        $this->expectEmailCreation($tokenValue, $emailAddress, $email);
        $this->expectMailerSend($email);
        $this->expectLogInfo($emailAddress);

        $this->subscriber->__invoke($event);
    }

    public function testSubscribedTo(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        $this->assertIsArray($subscribedEvents);
        $this->assertContains(PasswordResetEmailSentEvent::class, $subscribedEvents);
        $this->assertCount(1, $subscribedEvents);
    }

    private function createPasswordResetTokenMock(string $tokenValue): PasswordResetTokenInterface
    {
        $token = $this->createMock(PasswordResetTokenInterface::class);
        $token->expects($this->once())
            ->method('getTokenValue')
            ->willReturn($tokenValue);

        return $token;
    }

    private function expectEmailCreation(string $tokenValue, string $emailAddress, Email $email): void
    {
        $resetUrl = $this->apiBaseUrl . '/password-reset?token=' . urlencode($tokenValue);
        $subject = $this->faker->sentence();
        $text = $this->faker->text();

        $this->setupTranslatorExpectations($tokenValue, $subject, $text);
        $this->setupEmailFactoryExpectation($emailAddress, $subject, $text, $tokenValue, $resetUrl, $email);
    }

    private function setupTranslatorExpectations(string $tokenValue, string $subject, string $text): void
    {
        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap([
                ['email.password_reset.subject', [], null, null, $subject],
                ['email.password_reset.text', ['tokenValue' => $tokenValue], null, null, $text],
            ]);
    }

    private function setupEmailFactoryExpectation(
        string $emailAddress,
        string $subject,
        string $text,
        string $tokenValue,
        string $resetUrl,
        Email $email
    ): void {
        $this->emailFactory->expects($this->once())
            ->method('create')
            ->with(
                $emailAddress,
                $subject,
                $text,
                'email/password_reset.html.twig',
                [
                    'token' => $tokenValue,
                    'resetUrl' => $resetUrl,
                ]
            )
            ->willReturn($email);
    }

    private function expectMailerSend(Email $email): void
    {
        $this->mailer->expects($this->once())
            ->method('send')
            ->with($email);
    }

    private function expectLogInfo(string $emailAddress): void
    {
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Password reset token sent to ' . $emailAddress);
    }
}
