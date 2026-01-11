<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Application\Factory\EmailFactoryInterface;
use App\User\Domain\Event\PasswordResetEmailSentEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class PasswordResetEmailSentEventSubscriber implements
    DomainEventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        private EmailFactoryInterface $emailFactory,
        private string $apiBaseUrl
    ) {
    }

    public function __invoke(PasswordResetEmailSentEvent $event): void
    {
        $token = $event->token;
        $tokenValue = $token->getTokenValue();
        $emailAddress = $event->email;

        $resetUrl = $this->createResetUrl($tokenValue);
        $email = $this->createPasswordResetEmail(
            $emailAddress,
            $tokenValue,
            $resetUrl
        );

        $this->mailer->send($email);
        $this->logger->info('Password reset token sent to ' . $emailAddress);
    }

    /**
     * @return array<class-string<DomainEvent>>
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [PasswordResetEmailSentEvent::class];
    }

    private function createResetUrl(string $tokenValue): string
    {
        return $this->apiBaseUrl . '/password-reset?token=' .
            urlencode($tokenValue);
    }

    private function createPasswordResetEmail(
        string $emailAddress,
        string $tokenValue,
        string $resetUrl
    ): \Symfony\Component\Mime\Email {
        return $this->emailFactory->create(
            $emailAddress,
            $this->translator->trans('email.password_reset.subject'),
            $this->translator->trans(
                'email.password_reset.text',
                ['tokenValue' => $tokenValue]
            ),
            'email/password_reset.html.twig',
            [
                'token' => $tokenValue,
                'resetUrl' => $resetUrl,
            ]
        );
    }
}
