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
use App\User\Domain\Repository\UserRepositoryInterface;

final readonly class PasswordResetRequestedEventSubscriber implements
    DomainEventSubscriberInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private PasswordResetTokenRepositoryInterface $tokenRepository,
        private PasswordResetEmailFactoryInterface $emailFactory,
        private SendPasswordResetEmailCommandFactoryInterface $cmdFactory,
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function __invoke(PasswordResetRequestedEvent $event): void
    {
        $user = $this->userRepository->findById($event->userId);
        $token = $this->tokenRepository->findByToken($event->token);

        if (!$token instanceof PasswordResetTokenInterface || $user === null) {
            return;
        }

        $this->commandBus->dispatch(
            $this->cmdFactory->create(
                $this->emailFactory->create($token, $user)
            )
        );
    }

    /**
     * @return string[]
     *
     * @psalm-return list{PasswordResetRequestedEvent::class}
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [PasswordResetRequestedEvent::class];
    }
}
