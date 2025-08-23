<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\Command\RequestPasswordResetCommandResponse;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordResetRequestedEvent;
use App\User\Domain\Exception\PasswordResetRateLimitExceededException;
use App\User\Domain\Factory\PasswordResetTokenFactoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class RequestPasswordResetCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordResetTokenRepositoryInterface $passwordResetTokenRepository,
        private PasswordResetTokenFactoryInterface $passwordResetTokenFactory,
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
        private int $rateLimitMaxRequests = 3,
        private int $rateLimitWindowHours = 1,
    ) {
    }

    public function __invoke(RequestPasswordResetCommand $command): void
    {
        // Always return success message for security (no information disclosure)
        $successMessage = 'If the email address is valid, you will receive a password reset link.';

        $user = $this->userRepository->findByEmail($command->email);

        // If user doesn't exist, still return success message
        if (!$user instanceof UserInterface) {
            $command->setResponse(new RequestPasswordResetCommandResponse($successMessage));
            return;
        }

        // Check rate limiting
        $since = new \DateTimeImmutable("-{$this->rateLimitWindowHours} hours");
        $recentRequests = $this->passwordResetTokenRepository->countRecentRequestsByEmail(
            $command->email,
            $since
        );

        if ($recentRequests >= $this->rateLimitMaxRequests) {
            throw new PasswordResetRateLimitExceededException();
        }

        // Create new password reset token
        $passwordResetToken = $this->passwordResetTokenFactory->create($user->getId());
        $this->passwordResetTokenRepository->save($passwordResetToken);

        // Publish event
        $this->eventBus->publish(
            new PasswordResetRequestedEvent(
                $user,
                $passwordResetToken->getTokenValue(),
                (string) $this->uuidFactory->create()
            )
        );

        $command->setResponse(new RequestPasswordResetCommandResponse($successMessage));
    }
}
