<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\DTO\RequestPasswordResetCommandResponse;
use App\User\Application\Query\FindUserByEmailQueryHandlerInterface;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\DuplicateEmailException;
use App\User\Domain\Factory\Event\PasswordResetRequestedEventFactoryInterface;
use App\User\Domain\Factory\PasswordResetTokenFactoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use LogicException;
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
            // Treat an ambiguous (duplicate) email like a not-found lookup so
            // the duplicate-email branch flows through the same constant-time
            // token-generation path below instead of short-circuiting here.
            $user = null;
        }

        // Always generate a token so every branch performs the same
        // CSPRNG work, preventing a user-enumeration timing oracle
        // (CWE-208) on the deliberately uniform 204 response.
        $token = $this->tokenFactory->create(
            $user instanceof UserInterface ? $user->getId() : ''
        );

        if (!$user instanceof UserInterface) {
            return new RequestPasswordResetCommandResponse();
        }

        $this->persistAndPublish($user, $token);

        return new RequestPasswordResetCommandResponse();
    }

    private function persistAndPublish(
        UserInterface $user,
        PasswordResetTokenInterface $token
    ): void {
        $this->tokenRepository->save($token);

        $this->eventBus->publish(
            $this->eventFactory->create(
                $user,
                $token->getPlainToken() ?? throw new LogicException(
                    'Password reset plain token is missing.'
                ),
                (string) $this->uuidFactory->create()
            )
        );
    }
}
