<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\SendConfirmationEmailCommand;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class SendConfirmationEmailCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
        private ConfirmationEmailSendEventFactoryInterface $eventFactory
    ) {
    }

    public function __invoke(SendConfirmationEmailCommand $command): void
    {
        $confirmationEmail = $command->confirmationEmail;

        $confirmationEmail->send(
            (string) $this->uuidFactory->create(),
            $this->eventFactory
        );

        $this->eventBus->publish(...$confirmationEmail->pullDomainEvents());
    }
}
