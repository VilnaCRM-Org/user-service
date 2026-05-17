<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\DTO\RegisterUserCommandResponse;
use App\User\Application\Service\EmailNormalizer;
use App\User\Application\Transformer\SignUpTransformer;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\Event\UserRegisteredEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;
use Throwable;

final readonly class RegisterUserCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private PasswordHasherFactoryInterface $hasherFactory,
        private UserRepositoryInterface $userRepository,
        private SignUpTransformer $transformer,
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
        private UserRegisteredEventFactoryInterface $registeredEventFactory,
        private EmailNormalizer $emailNormalizer,
    ) {
    }

    public function __invoke(
        RegisterUserCommand $command
    ): RegisterUserCommandResponse {
        $command = $this->normalizeCommandEmail($command);
        $existingUser = $this->userRepository->findByEmail($command->email);

        if ($existingUser !== null) {
            return new RegisterUserCommandResponse($existingUser);
        }

        $user = $this->transformer->transformToUser($command);

        $hasher = $this->hasherFactory->getPasswordHasher(User::class);
        $hashedPassword = $hasher->hash($user->getPassword());
        $user->setPassword($hashedPassword);

        $raceWinner = $this->saveOrLoadRaceWinner($user, $command->email);
        if ($raceWinner !== null) {
            return new RegisterUserCommandResponse($raceWinner);
        }

        $this->eventBus->publish(
            $this->registeredEventFactory->create(
                $user,
                (string) $this->uuidFactory->create()
            )
        );

        return new RegisterUserCommandResponse($user);
    }

    private function normalizeCommandEmail(
        RegisterUserCommand $command
    ): RegisterUserCommand {
        return new RegisterUserCommand(
            $this->emailNormalizer->normalize($command->email),
            $command->initials,
            $command->password
        );
    }

    private function saveOrLoadRaceWinner(
        User $user,
        string $email,
    ): ?UserInterface {
        try {
            $this->userRepository->save($user);

            return null;
        } catch (Throwable $error) {
            $existingUser = $this->userRepository->findByEmail($email);

            if ($existingUser !== null) {
                return $existingUser;
            }

            throw $error;
        }
    }
}
