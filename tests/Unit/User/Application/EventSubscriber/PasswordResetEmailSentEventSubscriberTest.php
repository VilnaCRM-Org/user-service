<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\PasswordResetEmailSentEventSubscriber;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Event\PasswordResetEmailSentEvent;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Infrastructure\Factory\EmailFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PasswordResetEmailSentEventSubscriberTest extends UnitTestCase
{
    private MailerInterface $mailer;
    private PasswordResetTokenRepositoryInterface $tokenRepository;
    private LoggerInterface $logger;
    private TranslatorInterface $translator;
    private EmailFactoryInterface $emailFactory;
    private PasswordResetEmailSentEventSubscriber $subscriber;
    private string $apiBaseUrl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailer = $this->createMock(MailerInterface::class);
        $this->tokenRepository = $this->createMock(PasswordResetTokenRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->emailFactory = $this->createMock(EmailFactoryInterface::class);
        $this->apiBaseUrl = 'https://example.com';

        $this->subscriber = new PasswordResetEmailSentEventSubscriber(
            $this->mailer,
            $this->tokenRepository,
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
        $subject = $this->faker->sentence();
        $text = $this->faker->text();
        $resetUrl = $this->apiBaseUrl . '/password-reset?token=' . urlencode($tokenValue);

        $token = $this->createMock(PasswordResetTokenInterface::class);
        $token->expects($this->once())
            ->method('getTokenValue')
            ->willReturn($tokenValue);

        $event = new PasswordResetEmailSentEvent($token, $emailAddress, $eventId);

        $email = $this->createMock(Email::class);

        $this->tokenRepository->expects($this->once())
            ->method('save')
            ->with($token);

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap([
                ['email.password_reset.subject', [], null, null, $subject],
                ['email.password_reset.text', ['tokenValue' => $tokenValue], null, null, $text],
            ]);

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

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($email);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Password reset token sent to ' . $emailAddress);

        $this->subscriber->__invoke($event);
    }

    public function testSubscribedTo(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        $this->assertIsArray($subscribedEvents);
        $this->assertContains(PasswordResetEmailSentEvent::class, $subscribedEvents);
        $this->assertCount(1, $subscribedEvents);
    }
}
