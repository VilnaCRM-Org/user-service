<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\SendConfirmationEmailCommand;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class SendConfirmationEmailCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private readonly EventBusInterface $eventBus,
        private readonly UuidFactory $uuidFactory
    ) {
    }

    public function __invoke(SendConfirmationEmailCommand $command): void
    {
        $confirmationEmail = $command->confirmationEmail;

        $confirmationEmail->send((string) $this->uuidFactory->create());

        $this->eventBus->publish(...$confirmationEmail->pullDomainEvents());
    }
}
