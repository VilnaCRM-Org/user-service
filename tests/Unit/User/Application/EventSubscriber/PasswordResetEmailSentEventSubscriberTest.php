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

        $token = $this->createPasswordResetTokenMock($tokenValue);
        $event = new PasswordResetEmailSentEvent($token, $emailAddress, $eventId);
        $email = $this->createMock(Email::class);

        $this->expectTokenSave($token);
        $this->expectTranslations($tokenValue);
        $this->expectEmailCreation($tokenValue, $emailAddress, $email);
        $this->expectMailerSend($email);
        $this->expectLogInfo($emailAddress);

        $this->subscriber->__invoke($event);
    }

    private function createPasswordResetTokenMock(string $tokenValue): PasswordResetTokenInterface
    {
        $token = $this->createMock(PasswordResetTokenInterface::class);
        $token->expects($this->once())
            ->method('getTokenValue')
            ->willReturn($tokenValue);

        return $token;
    }

    private function expectTokenSave(PasswordResetTokenInterface $token): void
    {
        $this->tokenRepository->expects($this->once())
            ->method('save')
            ->with($token);
    }

    private function expectTranslations(string $tokenValue): void
    {
        $subject = $this->faker->sentence();
        $text = $this->faker->text();

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap([
                ['email.password_reset.subject', [], null, null, $subject],
                ['email.password_reset.text', ['tokenValue' => $tokenValue], null, null, $text],
            ]);
    }

    private function expectEmailCreation(string $tokenValue, string $emailAddress, Email $email): void
    {
        $resetUrl = $this->apiBaseUrl . '/password-reset?token=' . urlencode($tokenValue);

        $this->emailFactory->expects($this->once())
            ->method('create')
            ->with(
                $emailAddress,
                $this->faker->sentence(),
                $this->faker->text(),
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

    public function testSubscribedTo(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        $this->assertIsArray($subscribedEvents);
        $this->assertContains(PasswordResetEmailSentEvent::class, $subscribedEvents);
        $this->assertCount(1, $subscribedEvents);
    }
}
