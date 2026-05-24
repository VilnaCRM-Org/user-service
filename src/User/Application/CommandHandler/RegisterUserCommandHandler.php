<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\DTO\RegisterUserCommandResponse;
use App\User\Application\Factory\UserPasswordHashFactory;
use App\User\Application\Query\FindUserByEmailQueryHandlerInterface;
use App\User\Application\Service\EmailNormalizer;
use App\User\Application\Transformer\SignUpTransformer;
use App\User\Domain\Exception\DuplicateEmailException;
use App\User\Domain\Factory\Event\UserRegisteredEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class RegisterUserCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private UserPasswordHashFactory $passwordHashFactory,
        private UserRepositoryInterface $userRepository,
        private FindUserByEmailQueryHandlerInterface $findUserByEmailQueryHandler,
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
        if ($this->findUserByEmailQueryHandler->find($command->email) !== null) {
            throw new DuplicateEmailException($command->email);
        }

        $user = $this->transformer->transformToUser($command);

        $user->setPassword(
            $this->passwordHashFactory->create($user->getPassword())
        );

        $this->userRepository->save($user);

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
}
