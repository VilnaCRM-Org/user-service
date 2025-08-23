<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Domain\Event\PasswordResetConfirmedEvent;
use App\User\Infrastructure\Factory\EmailFactoryInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class PasswordResetConfirmedEventSubscriber implements
    DomainEventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private EmailFactoryInterface $emailFactory,
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(
        PasswordResetConfirmedEvent $passwordResetConfirmedEvent
    ): void {
        $emailAddress = $passwordResetConfirmedEvent->user->getEmail();

        // Send confirmation email
        $email = $this->emailFactory->create(
            $emailAddress,
            $this->translator->trans('email.password.reset.confirmed.subject'),
            $this->translator->trans('email.password.reset.confirmed.text'),
            'email/confirm.html.twig'
        );

        $this->mailer->send($email);
    }

    /**
     * @return array<DomainEvent>
     */
    public function subscribedTo(): array
    {
        return [PasswordResetConfirmedEvent::class];
    }
}
