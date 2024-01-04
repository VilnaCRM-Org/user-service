<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventHandler;

use App\Shared\Domain\Bus\Event\DomainEventSubscriber;
use App\User\Infrastructure\Event\PasswordChangedEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PasswordChangedEventHandler implements DomainEventSubscriber
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function __invoke(PasswordChangedEvent $passwordChangedEvent): void
    {
        $emailAddress = $passwordChangedEvent->email;

        $email = (new Email())
            ->to($emailAddress)
            ->subject('Password Change Notification')
            ->text('Your account password has been updated. If you made this change, no action is needed. 
            If not, or if you have concerns, contact our support team ')
            ->html('<p>See Twig integration for better HTML integration!</p>');

        $this->mailer->send($email);
    }

    public static function subscribedTo(): array
    {
        return [PasswordChangedEvent::class];
    }
}
