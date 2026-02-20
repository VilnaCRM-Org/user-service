<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\DisableTwoFactorCommand;
use App\User\Application\Service\TwoFactorCodeVerifierInterface;
use App\User\Application\Service\TwoFactorEventPublisherInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 */
final readonly class DisableTwoFactorCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RecoveryCodeRepositoryInterface $recoveryCodeRepository,
        private TwoFactorCodeVerifierInterface $codeVerifier,
        private TwoFactorEventPublisherInterface $eventPublisher,
    ) {
    }

    public function __invoke(DisableTwoFactorCommand $command): void
    {
        $user = $this->resolveUser($command->userEmail);
        $this->codeVerifier->verifyAndConsumeOrFail($user, $command->twoFactorCode);

        $user->setTwoFactorEnabled(false);
        $user->setTwoFactorSecret(null);
        $this->userRepository->save($user);

        $this->recoveryCodeRepository->deleteByUserId($user->getId());

        $this->eventPublisher->publishDisabled(
            $user->getId(),
            $user->getEmail()
        );
    }

    private function resolveUser(string $email): User
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user instanceof User) {
            throw new UnauthorizedHttpException(
                'Bearer',
                'Authentication required.'
            );
        }

        if (!$user->isTwoFactorEnabled()) {
            throw new AccessDeniedHttpException(
                'Two-factor authentication is not enabled.'
            );
        }

        return $user;
    }
}
