<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\DTO\RequestPasswordResetCommandResponse;
use App\User\Application\Query\FindUserByEmailQueryHandlerInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\DuplicateEmailException;
use App\User\Domain\Factory\Event\PasswordResetRequestedEventFactoryInterface;
use App\User\Domain\Factory\PasswordResetTokenFactoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class RequestPasswordResetCommandHandler implements
    CommandHandlerInterface,
    RequestPasswordResetHandlerInterface
{
    public function __construct(
        private FindUserByEmailQueryHandlerInterface $findUserByEmailQueryHandler,
        private PasswordResetTokenRepositoryInterface $tokenRepository,
        private PasswordResetTokenFactoryInterface $tokenFactory,
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
        private PasswordResetRequestedEventFactoryInterface $eventFactory,
    ) {
    }

    #[\Override]
    public function __invoke(
        RequestPasswordResetCommand $command
    ): RequestPasswordResetCommandResponse {
        try {
            $user = $this->findUserByEmailQueryHandler->find($command->email);
        } catch (DuplicateEmailException) {
            return new RequestPasswordResetCommandResponse();
        }

        if (!$user instanceof UserInterface) {
            return new RequestPasswordResetCommandResponse();
        }

        $token = $this->tokenFactory->create($user->getId());
        $this->tokenRepository->save($token);

        $this->eventBus->publish(
            $this->eventFactory->create(
                $user,
                $token->getTokenValue(),
                (string) $this->uuidFactory->create()
            )
        );

        return new RequestPasswordResetCommandResponse();
    }
}
