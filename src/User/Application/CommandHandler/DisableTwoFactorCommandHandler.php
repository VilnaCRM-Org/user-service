<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\DisableTwoFactorCommand;
use App\User\Application\Resolver\AuthenticatedUserResolver;
use App\User\Application\Validator\TwoFactorCodeValidatorInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Publisher\TwoFactorPublisherInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @psalm-api
 */
final readonly class DisableTwoFactorCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AuthenticatedUserResolver $authenticatedUserResolver,
        private RecoveryCodeRepositoryInterface $recoveryCodeRepository,
        private TwoFactorCodeValidatorInterface $twoFactorCodeVerifier,
        private TwoFactorPublisherInterface $events,
    ) {
    }

    public function __invoke(DisableTwoFactorCommand $command): void
    {
        $user = $this->resolveUser($command->userEmail);
        $this->twoFactorCodeVerifier->verifyAndConsumeOrFail($user, $command->twoFactorCode);

        $user->disableTwoFactor();
        $this->userRepository->save($user);

        $this->recoveryCodeRepository->deleteByUserId($user->getId());

        $this->events->publishDisabled(
            $user->getId(),
            $user->getEmail()
        );
    }

    private function resolveUser(string $email): User
    {
        $user = $this->authenticatedUserResolver->resolve($email);

        if (!$user->isTwoFactorEnabled()) {
            throw new AccessDeniedHttpException('Two-factor authentication is not enabled.');
        }

        return $user;
    }
}
