<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Domain\Event\PasswordResetRequestedEvent;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Infrastructure\Factory\EmailFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PasswordResetRequestedEventSubscriber implements
    DomainEventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private TokenRepositoryInterface $tokenRepository,
        private LoggerInterface $logger,
        private EmailFactoryInterface $emailFactory,
        private TranslatorInterface $translator
    ) {
    }

    public function __invoke(PasswordResetRequestedEvent $event): void
    {
        $this->tokenRepository->save($event->token);

        $email = $this->emailFactory->create(
            $event->emailAddress,
            $this->translator->trans('email.password.reset.requested.subject'),
            $this->translator->trans(
                'email.password.reset.requested.test',
                ['tokenValue' => $event->token->getTokenValue()]
            ),
            'email/password-reset.html.twig'
        );

        $this->mailer->send($email);

        $this->logger->info(
            'Reset password token send to '.$event->emailAddress
        );
    }

    /**
     * @return array<DomainEvent>
     */
    public function subscribedTo(): array
    {
        return [PasswordResetRequestedEvent::class];
    }
}
