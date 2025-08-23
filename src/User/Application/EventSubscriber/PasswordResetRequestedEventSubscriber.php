<?php

declare(strict_types=1);

namespace App\User\Application\EventSubscriber;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Application\Factory\SendPasswordResetEmailCommandFactoryInterface;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Event\PasswordResetRequestedEvent;
use App\User\Domain\Factory\PasswordResetEmailFactoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;

final readonly class PasswordResetRequestedEventSubscriber implements
    DomainEventSubscriberInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private PasswordResetTokenRepositoryInterface $tokenRepository,
        private PasswordResetEmailFactoryInterface $emailFactory,
        private SendPasswordResetEmailCommandFactoryInterface $cmdFactory
    ) {
    }

    public function __invoke(PasswordResetRequestedEvent $event): void
    {
        $user = $event->user;

        // Find the password reset token that was created
        $token = $this->tokenRepository->findByToken($event->token);

        if (!$token instanceof PasswordResetTokenInterface) {
            return; // Token not found, skip email sending
        }

        $this->commandBus->dispatch(
            $this->cmdFactory->create(
                $this->emailFactory->create($token, $user)
            )
        );
    }

    /**
     * @return array<class-string<DomainEvent>>
     */
    public function subscribedTo(): array
    {
        return [PasswordResetRequestedEvent::class];
    }
}
