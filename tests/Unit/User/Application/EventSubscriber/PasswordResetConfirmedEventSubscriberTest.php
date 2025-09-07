<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\PasswordResetConfirmedEventSubscriber;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordResetConfirmedEvent;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Factory\EmailFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PasswordResetConfirmedEventSubscriberTest extends UnitTestCase
{
    private MailerInterface $mailer;
    private EmailFactoryInterface $emailFactory;
    private TranslatorInterface $translator;
    private UserRepositoryInterface $userRepository;
    private LoggerInterface $logger;
    private PasswordResetConfirmedEventSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailer = $this->createMock(MailerInterface::class);
        $this->emailFactory = $this->createMock(EmailFactoryInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->subscriber = new PasswordResetConfirmedEventSubscriber(
            $this->mailer,
            $this->emailFactory,
            $this->translator,
            $this->userRepository,
            $this->logger
        );
    }

    public function testInvokeSuccessfully(): void
    {
        $userId = $this->faker->uuid();
        $userEmail = $this->faker->safeEmail();
        $eventId = $this->faker->uuid();

        $user = $this->createUserMock($userEmail);
        $event = new PasswordResetConfirmedEvent($userId, $eventId);
        $email = $this->createMock(Email::class);

        $this->expectUserRepositoryFindById($userId, $user);
        $this->expectEmailCreation($userEmail, $email);
        $this->expectMailerSend($email);

        $this->subscriber->__invoke($event);
    }

    public function testInvokeWithUserNotFound(): void
    {
        $userId = $this->faker->uuid();
        $eventId = $this->faker->uuid();

        $event = new PasswordResetConfirmedEvent($userId, $eventId);

        $this->expectUserRepositoryFindById($userId, null);
        $this->expectLoggerWarning($userId);

        $this->mailer->expects($this->never())->method('send');

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

    private function expectUserRepositoryFindById(string $userId, ?UserInterface $user): void
    {
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($userId)
            ->willReturn($user);
    }

    private function expectLoggerWarning(string $userId): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('User not found for password reset confirmation', ['userId' => $userId]);
    }

    private function expectEmailCreation(string $userEmail, Email $email): void
    {
        $subject = $this->faker->sentence();
        $text = $this->faker->text();

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap([
                ['email.password.reset.confirmed.subject', [], null, null, $subject],
                ['email.password.reset.confirmed.text', [], null, null, $text],
            ]);

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
