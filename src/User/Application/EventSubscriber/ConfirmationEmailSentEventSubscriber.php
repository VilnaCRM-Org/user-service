<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Domain\Event\ConfirmationEmailSentEvent;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Infrastructure\Factory\EmailFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ConfirmationEmailSentEventSubscriber implements
    DomainEventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private TokenRepositoryInterface $tokenRepository,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        private EmailFactoryInterface $emailFactory
    ) {
    }

    public function __invoke(ConfirmationEmailSentEvent $event): void
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

        $this->logger->info('Confirmation token send to ' . $emailAddress);
    }

    /**
     * @return array<DomainEvent>
     */
    public function subscribedTo(): array
    {
        return [ConfirmationEmailSentEvent::class];
    }
}
