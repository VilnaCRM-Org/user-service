<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\SendConfirmationEmailCommand;
use App\User\Application\Exception\UserTimedOutException;
use App\User\Domain\Exception\NotAllowedToSendException;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class SendConfirmationEmailCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory
    ) {
    }

    public function __invoke(SendConfirmationEmailCommand $command): void
    {
        $confirmationEmail = $command->confirmationEmail;

        try {
            $confirmationEmail->send((string)$this->uuidFactory->create());
        }
        catch (NotAllowedToSendException $exception){
            throw new UserTimedOutException($exception->datetime);
        }

        $this->eventBus->publish(...$confirmationEmail->pullDomainEvents());
    }
}
