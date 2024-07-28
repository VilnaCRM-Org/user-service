<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Tests\Builders\ConfirmationTokenBuilder;
use App\Tests\Builders\UserBuilder;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\PasswordResetRequestedEventSubscriber;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\PasswordResetRequestedEvent;
use App\User\Domain\Factory\Event\PasswordResetRequestedEventFactory;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Infrastructure\Factory\EmailFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PasswordResetRequestedEventSubscriberTest extends UnitTestCase
{
    private Email $emailStub;

    private TranslatorInterface $translatorMock;
    private EmailFactoryInterface $emailFactoryMock;
    private LoggerInterface $loggerMock;
    private MailerInterface $mailerMock;

    private TokenRepositoryInterface $tokenRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->emailStub = $this->createStub(Email::class);

        $this->translatorMock = $this->createMock(TranslatorInterface::class);
        $this->emailFactoryMock = $this->createMock(EmailFactoryInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->mailerMock = $this->createMock(MailerInterface::class);

        $this->tokenRepositoryMock = $this->createMock(TokenRepositoryInterface::class);
    }

    public function testInvoke(): void
    {
        $user = (new UserBuilder())->build();
        $confirmationToken = (new ConfirmationTokenBuilder())->build();

        $this->tokenRepositoryMock->expects($this->once())->method('save')
            ->with($confirmationToken);
        $this->translatorMock->expects($this->exactly(2))->method('trans')
            ->withConsecutive(
                ['email.password.reset.requested.subject'],
                ['email.password.reset.requested.text', ['tokenValue' => $confirmationToken->getTokenValue()]]
            )
            ->willReturnOnConsecutiveCalls('subject-translation', 'text-translation');
        $this->emailFactoryMock->expects($this->once())->method('create')
            ->with(
                $user->getEmail(),
                'subject-translation',
                'text-translation',
                'email/password-reset.html.twig'
            )
            ->willReturn($this->emailStub);
        $this->mailerMock->expects($this->once())->method('send')
            ->with($this->emailStub);
        $this->loggerMock->expects($this->once())->method('info')
            ->with('Reset password token send to ' . $user->getEmail());

        $event = $this->getEvent($confirmationToken, $user);
        $this->getSubscriber()->__invoke($event);
    }

    public function testSubscribedTo(): void
    {
        $this->assertSame(
            [PasswordResetRequestedEvent::class],
            $this->getSubscriber()->subscribedTo()
        );
    }

    private function getSubscriber(): PasswordResetRequestedEventSubscriber
    {
        return new PasswordResetRequestedEventSubscriber(
            $this->mailerMock,
            $this->tokenRepositoryMock,
            $this->loggerMock,
            $this->emailFactoryMock,
            $this->translatorMock,
        );
    }

    private function getEvent(ConfirmationToken $token, User $user): PasswordResetRequestedEvent
    {
        return (new PasswordResetRequestedEventFactory())->create(
            $token,
            $user,
            $this->faker->uuid()
        );
    }
}
