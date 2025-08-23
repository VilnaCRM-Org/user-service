<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Domain\Event\PasswordResetEmailSentEvent;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Infrastructure\Factory\EmailFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class PasswordResetEmailSentEventSubscriber implements
    DomainEventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private PasswordResetTokenRepositoryInterface $passwordResetTokenRepository,
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

        // The token should already be saved by the command handler
        // but we can save it again to ensure consistency
        $this->passwordResetTokenRepository->save($token);

        // Create the reset URL
        $resetUrl = $this->apiBaseUrl . '/password-reset?token=' . urlencode($tokenValue);

        $email = $this->emailFactory->create(
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

        $this->mailer->send($email);

        $this->logger->info('Password reset token sent to ' . $emailAddress);
    }

    /**
     * @return array<class-string<DomainEvent>>
     */
    public function subscribedTo(): array
    {
        return [PasswordResetEmailSentEvent::class];
    }
}
