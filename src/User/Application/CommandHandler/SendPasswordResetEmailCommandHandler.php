<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\SendPasswordResetEmailCommand;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class SendPasswordResetEmailCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory
    ) {
    }

    public function __invoke(SendPasswordResetEmailCommand $command): void
    {
        $passwordResetEmail = $command->passwordResetEmail;

        $passwordResetEmail->send((string) $this->uuidFactory->create());

        $this->eventBus->publish(...$passwordResetEmail->pullDomainEvents());
    }
}
