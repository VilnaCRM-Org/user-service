<?php

namespace App\User\Infrastructure\Event;

use App\Shared\Domain\Bus\Event\DomainEventSubscriber;
use App\User\Domain\TokenRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ConfirmationEmailSendEventHandler implements DomainEventSubscriber
{
    public function __construct(private MailerInterface $mailer,
        private TokenRepositoryInterface $tokenRepository,
        private LoggerInterface $logger, )
    {
    }

    public static function subscribedTo(): array
    {
        return [ConfirmationEmailSendEvent::class];
    }

    public function __invoke(ConfirmationEmailSendEvent $event)
    {
        $token = $event->token;
        $tokenValue = $token->getTokenValue();
        $emailAddress = $event->emailAddress;

        $this->tokenRepository->save($token);

        $email = (new Email())
            ->to($emailAddress)
            ->subject('VilnaCRM email confirmation')
            ->text("Your email confirmation token - $tokenValue")
            ->html('<p>See Twig integration for better HTML integration!</p>');

        $this->mailer->send($email);

        $this->logger->info('Send confirmation token to '.$emailAddress);
    }
}
