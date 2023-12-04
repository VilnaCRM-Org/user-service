<?php

namespace App\User\Infrastructure\Event;

use App\Shared\Domain\Bus\Event\DomainEventSubscriber;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PasswordChangedEventHandler implements DomainEventSubscriber
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public static function subscribedTo(): array
    {
        return [PasswordChangedEvent::class];
    }

    public function __invoke(PasswordChangedEvent $passwordChangedEvent)
    {
        $emailAddress = $passwordChangedEvent->getEmail();

        $email = (new Email())
            ->to($emailAddress)
            ->subject('Password Change Notification')
            ->text("Your account password has been updated. If you made this change, no action is needed. If not, or if you have concerns, contact our support team ")
            ->html('<p>See Twig integration for better HTML integration!</p>');

        $this->mailer->send($email);

    }
}
