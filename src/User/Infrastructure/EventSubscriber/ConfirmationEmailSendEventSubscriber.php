<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Domain\Event\ConfirmationEmailSendEvent;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Infrastructure\Factory\EmailFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ConfirmationEmailSendEventSubscriber implements
    DomainEventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private TokenRepositoryInterface $tokenRepository,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        private EmailFactory $emailFactory
    ) {
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
            $this->translator->trans(
                'email.confirm.text',
                ['tokenValue' => $tokenValue]
            ),
            'email/confirm.html.twig'
        );

        $this->mailer->send($email);

        $this->logger->info('Confirmation token send to '.$emailAddress);
    }

    /**
     * @return array<DomainEvent>
     */
    public static function subscribedTo(): array
    {
        return [ConfirmationEmailSendEvent::class];
    }
}
