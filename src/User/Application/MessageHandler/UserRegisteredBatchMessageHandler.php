<?php

declare(strict_types=1);

namespace App\User\Application\MessageHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Message\UserRegisteredMessage;
use App\User\Domain\Factory\Event\UserRegisteredEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Handler\Acknowledger;
use Symfony\Component\Messenger\Handler\BatchHandlerInterface;
use Symfony\Component\Messenger\Handler\BatchHandlerTrait;
use Symfony\Component\Uid\Factory\UuidFactory;

final class UserRegisteredBatchMessageHandler implements BatchHandlerInterface
{
    use BatchHandlerTrait;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventBusInterface $eventBus,
        private UserRegisteredEventFactoryInterface $registeredEventFactory,
        private UuidFactory $uuidFactory,
    ) {
    }

    public function __invoke(
        UserRegisteredMessage $message,
        Acknowledger $ack
    ): mixed {
        return $this->handle($message, $ack);
    }

    /**
     * @param array<UserRegisteredMessage, Acknowledger> $jobs
     */
    private function process(array $jobs): void
    {
        foreach ($jobs as [$message, $ack]) {
            $this->handleJob($message, $ack);
        }
    }

    private function handleJob(
        UserRegisteredMessage $message,
        Acknowledger $ack
    ): void {
        try {
            $user = $message->user;
            $this->userRepository->save($user);
            $this->eventBus->publish(
                $this->registeredEventFactory->create(
                    $user,
                    (string) $this->uuidFactory->create()
                )
            );

            $ack->ack($user);
        } catch (\Throwable $e) {
            $ack->nack($e);
        }
    }
}
