<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\PasswordResetConfirmedEventSubscriber;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordResetConfirmedEvent;
use App\User\Infrastructure\Factory\EmailFactoryInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PasswordResetConfirmedEventSubscriberTest extends UnitTestCase
{
    private MailerInterface $mailer;
    private EmailFactoryInterface $emailFactory;
    private TranslatorInterface $translator;
    private PasswordResetConfirmedEventSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailer = $this->createMock(MailerInterface::class);
        $this->emailFactory = $this->createMock(EmailFactoryInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->subscriber = new PasswordResetConfirmedEventSubscriber(
            $this->mailer,
            $this->emailFactory,
            $this->translator
        );
    }

    public function testInvokeSuccessfully(): void
    {
        $userEmail = $this->faker->safeEmail();
        $eventId = $this->faker->uuid();

        $user = $this->createUserMock($userEmail);
        $event = new PasswordResetConfirmedEvent($user, $eventId);
        $email = $this->createMock(Email::class);

        $this->expectTranslations();
        $this->expectEmailCreation($userEmail, $email);
        $this->expectMailerSend($email);

        $this->subscriber->__invoke($event);
    }

    private function createUserMock(string $userEmail): UserInterface
    {
        $user = $this->createMock(UserInterface::class);
        $user->expects($this->once())
            ->method('getEmail')
            ->willReturn($userEmail);

        return $user;
    }

    private function expectTranslations(): void
    {
        $subject = $this->faker->sentence();
        $text = $this->faker->text();

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap([
                ['email.password.reset.confirmed.subject', [], null, null, $subject],
                ['email.password.reset.confirmed.text', [], null, null, $text],
            ]);
    }

    private function expectEmailCreation(string $userEmail, Email $email): void
    {
        $subject = $this->faker->sentence();
        $text = $this->faker->text();

        $this->emailFactory->expects($this->once())
            ->method('create')
            ->with(
                $userEmail,
                $subject,
                $text,
                'email/confirm.html.twig'
            )
            ->willReturn($email);
    }

    private function expectMailerSend(Email $email): void
    {
        $this->mailer->expects($this->once())
            ->method('send')
            ->with($email);
    }

    public function testSubscribedTo(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        $this->assertIsArray($subscribedEvents);
        $this->assertContains(PasswordResetConfirmedEvent::class, $subscribedEvents);
        $this->assertCount(1, $subscribedEvents);
    }
}
