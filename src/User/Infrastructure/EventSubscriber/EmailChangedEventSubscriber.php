<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventSubscriber;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Application\Command\SendConfirmationEmailCommand;
use App\User\Domain\Aggregate\ConfirmationEmail;
use App\User\Domain\Event\EmailChangedEvent;
use App\User\Domain\Factory\ConfirmationTokenFactory;

class EmailChangedEventSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(private CommandBusInterface $commandBus, private ConfirmationTokenFactory $tokenFactory)
    {
    }

    public function __invoke(EmailChangedEvent $event): void
    {
        $user = $event->user;
        $token = $this->tokenFactory->create($user->getId());

        $this->commandBus->dispatch(new SendConfirmationEmailCommand(new ConfirmationEmail($token, $user)));
    }

    /**
     * @return array<DomainEvent>
     */
    public static function subscribedTo(): array
    {
        return [EmailChangedEvent::class];
    }
}
