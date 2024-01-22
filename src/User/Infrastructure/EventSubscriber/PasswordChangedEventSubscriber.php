<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Domain\Event\PasswordChangedEvent;
use App\User\Infrastructure\Factory\EmailFactory;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PasswordChangedEventSubscriber implements
    DomainEventSubscriberInterface
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

    /**
     * @return array<DomainEvent>
     */
    public static function subscribedTo(): array
    {
        return [PasswordChangedEvent::class];
    }
}
