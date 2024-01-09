<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventHandler;

use App\Shared\Domain\Bus\Event\DomainEventSubscriber;
use App\User\Domain\Factory\EmailFactory;
use App\User\Domain\TokenRepositoryInterface;
use App\User\Infrastructure\Event\ConfirmationEmailSendEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfirmationEmailSendEventHandler implements DomainEventSubscriber
{
    public function __construct(private MailerInterface $mailer,
        private TokenRepositoryInterface $tokenRepository,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        private EmailFactory $emailFactory
    ) {
    }

    public static function subscribedTo(): array
    {
        return [ConfirmationEmailSendEvent::class];
    }

    public function __invoke(ConfirmationEmailSendEvent $event): void
    {
        $token = $event->token;
        $tokenValue = $token->getTokenValue();
        $emailAddress = $event->emailAddress;

        $this->tokenRepository->save($token);

        $email = $this->emailFactory->create(
            $emailAddress,
            $this->translator->trans('email.confirm.subject'),
            $this->translator->trans('email.confirm.text', ['tokenValue' => $tokenValue])
        );

        $this->mailer->send($email);

        $this->logger->info('Confirmation token send to '.$emailAddress);
    }
}
