<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventHandler;

use App\Shared\Domain\Bus\Event\DomainEventSubscriber;
use App\User\Domain\Event\PasswordChangedEvent;
use App\User\Domain\Factory\EmailFactory;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PasswordChangedEventHandler implements DomainEventSubscriber
{
    public function __construct(
        private MailerInterface $mailer,
        private EmailFactory $emailFactory,
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(PasswordChangedEvent $passwordChangedEvent): void
    {
        $emailAddress = $passwordChangedEvent->email;

        $email = $this->emailFactory->create(
            $emailAddress,
            $this->translator->trans('email.password.changed.subject'),
            $this->translator->trans('email.password.changed.text'),
            'email/confirm.html.twig'
        );

        $this->mailer->send($email);
    }

    public static function subscribedTo(): array
    {
        return [PasswordChangedEvent::class];
    }
}
