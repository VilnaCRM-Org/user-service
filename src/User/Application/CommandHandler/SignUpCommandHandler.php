<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\SignUpCommand;
use App\User\Application\Command\SignUpCommandResponse;
use App\User\Application\Transformer\SignUpTransformer;
use App\User\Domain\Event\UserRegisteredEvent;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class SignUpCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepositoryInterface $userRepository,
        private SignUpTransformer $transformer,
        private EventBusInterface $eventBus
    ) {
    }

    public function __invoke(SignUpCommand $command): void
    {
        $user = $this->transformer->transformToUser($command);
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $user->getPassword()
        );
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);
        $command->setResponse(new SignUpCommandResponse($user));

        $this->eventBus->publish(new UserRegisteredEvent($user));
    }
}
