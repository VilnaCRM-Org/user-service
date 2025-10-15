<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\Command\RequestPasswordResetCommandResponse;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordResetRequestedEvent;
use App\User\Domain\Exception\PasswordResetRateLimitExceededException;
use App\User\Domain\Factory\PasswordResetTokenFactoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class RequestPasswordResetCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordResetTokenRepositoryInterface $tokenRepository,
        private PasswordResetTokenFactoryInterface $tokenFactory,
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
        private int $rateLimitMaxRequests = 3,
        private int $rateLimitWindowHours = 1,
    ) {
    }

    public function __invoke(RequestPasswordResetCommand $command): void
    {
        $user = $this->userRepository->findByEmail($command->email);

        if (!$user instanceof UserInterface) {
            $command->setResponse(
                new RequestPasswordResetCommandResponse('')
            );
            return;
        }

        $this->checkRateLimit($command->email);
        $token = $this->createPasswordResetToken($user);
        $this->publishEvent($user, $token);

        $command->setResponse(
            new RequestPasswordResetCommandResponse('')
        );
    }

    private function checkRateLimit(string $email): void
    {
        $since = new DateTimeImmutable(
            "-{$this->rateLimitWindowHours} hours"
        );
        $recentRequests = $this->tokenRepository->countRecentRequestsByEmail(
            $email,
            $since
        );

        if ($recentRequests >= $this->rateLimitMaxRequests) {
            throw new PasswordResetRateLimitExceededException();
        }
    }

    private function createPasswordResetToken(
        UserInterface $user
    ): PasswordResetTokenInterface {
        $passwordResetToken = $this->tokenFactory->create($user->getId());
        $this->tokenRepository->save($passwordResetToken);

        return $passwordResetToken;
    }

    private function publishEvent(
        UserInterface $user,
        PasswordResetTokenInterface $token
    ): void {
        $this->eventBus->publish(
            new PasswordResetRequestedEvent(
                $user,
                $token->getTokenValue(),
                (string) $this->uuidFactory->create()
            )
        );
    }
}
