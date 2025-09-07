<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordResetConfirmedEvent;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Factory\EmailFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class PasswordResetConfirmedEventSubscriber implements
    DomainEventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private EmailFactoryInterface $emailFactory,
        private TranslatorInterface $translator,
        private UserRepositoryInterface $userRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(
        PasswordResetConfirmedEvent $passwordResetConfirmedEvent
    ): void {
        $user = $this->userRepository->findById(
            $passwordResetConfirmedEvent->userId
        );

        if (!$user instanceof UserInterface) {
            $this->logger->warning(
                'User not found for password reset confirmation',
                ['userId' => $passwordResetConfirmedEvent->userId]
            );
            return;
        }

        $emailAddress = $user->getEmail();

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
