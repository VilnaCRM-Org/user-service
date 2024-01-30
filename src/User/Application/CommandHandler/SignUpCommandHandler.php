<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\SignUpCommand;
use App\User\Application\Command\SignUpCommandResponse;
use App\User\Application\Transformer\SignUpTransformer;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\Event\UserRegisteredEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class SignUpCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private PasswordHasherFactoryInterface $hasherFactory,
        private UserRepositoryInterface $userRepository,
        private SignUpTransformer $transformer,
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
        private UserRegisteredEventFactoryInterface $registeredEventFactory,
    ) {
    }

    public function __invoke(SignUpCommand $command): void
    {
        $user = $this->transformer->transformToUser($command);

        $hasher = $this->hasherFactory->getPasswordHasher(User::class);
        $hashedPassword = $hasher->hash($user->getPassword());
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);
        $command->setResponse(new SignUpCommandResponse($user));

        $this->eventBus->publish(
            $this->registeredEventFactory->create(
                $user,
                (string) $this->uuidFactory->create()
            )
        );
    }
}
