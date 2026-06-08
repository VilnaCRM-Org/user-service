<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\RegenerateRecoveryCodesCommand;
use App\User\Application\DTO\RegenerateRecoveryCodesCommandResponse;
use App\User\Application\Factory\RecoveryCodeBatchFactoryInterface;
use App\User\Application\Query\FindUserByEmailQueryHandlerInterface;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\RecoveryCodeRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @psalm-api
 */
final readonly class RegenerateRecoveryCodesCommandHandler implements CommandHandlerInterface
{
    private const SUDO_MODE_TTL_SECONDS = 300;

    public function __construct(
        private FindUserByEmailQueryHandlerInterface $findUserByEmailQueryHandler,
        private RecoveryCodeRepositoryInterface $recoveryCodeRepository,
        private AuthSessionRepositoryInterface $authSessionRepository,
        private RecoveryCodeBatchFactoryInterface $recoveryCodeBatchFactory,
        private CurrentTimestampProviderInterface $currentTimestampProvider,
    ) {
    }

    public function __invoke(
        RegenerateRecoveryCodesCommand $command
    ): RegenerateRecoveryCodesCommandResponse {
        $user = $this->resolveUser($command->userEmail);
        $this->verifySudoMode($command->currentSessionId);

        $this->recoveryCodeRepository->deleteByUserId($user->getId());
        $codes = $this->recoveryCodeBatchFactory->create($user);

        return new RegenerateRecoveryCodesCommandResponse($codes);
    }

    private function resolveUser(string $email): User
    {
        $user = $this->findUserByEmailQueryHandler->find($email);
        if (!$user instanceof User) {
            throw new UnauthorizedHttpException('Bearer', 'Authentication required.');
        }

        if (!$user->isTwoFactorEnabled()) {
            throw new AccessDeniedHttpException('Two-factor authentication is not enabled.');
        }

        return $user;
    }

    private function verifySudoMode(string $sessionId): void
    {
        $session = $this->authSessionRepository->findById($sessionId);
        if (!$session instanceof AuthSession) {
            throw new AccessDeniedHttpException('Re-authentication required.');
        }

        if ($this->isSudoModeExpired($session)) {
            throw new AccessDeniedHttpException('Re-authentication required.');
        }
    }

    private function isSudoModeExpired(AuthSession $session): bool
    {
        $expiresAtTimestamp = $session->getCreatedAt()->getTimestamp()
            + self::SUDO_MODE_TTL_SECONDS;

        return $expiresAtTimestamp < $this->currentTimestampProvider->currentTimestamp();
    }
}
