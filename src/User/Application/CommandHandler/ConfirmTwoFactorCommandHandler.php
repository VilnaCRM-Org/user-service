<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\ConfirmTwoFactorCommand;
use App\User\Application\DTO\ConfirmTwoFactorCommandResponse;
use App\User\Application\EventPublisher\SessionEventsInterface;
use App\User\Application\EventPublisher\TwoFactorEventsInterface;
use App\User\Application\Generator\RecoveryCodeGeneratorInterface;
use App\User\Application\Verifier\TwoFactorCodeVerifierInterface;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @psalm-api
 */
final readonly class ConfirmTwoFactorCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AuthSessionRepositoryInterface $authSessionRepository,
        private TwoFactorCodeVerifierInterface $twoFactorCodeVerifier,
        private RecoveryCodeGeneratorInterface $recoveryCodeGenerator,
        private TwoFactorEventsInterface $events,
        private SessionEventsInterface $sessionEvents,
    ) {
    }

    public function __invoke(ConfirmTwoFactorCommand $command): void
    {
        $user = $this->resolveUser($command->userEmail);
        $this->verifyTotpOrFail($user, $command->twoFactorCode);

        $user->enableTwoFactor();
        $this->userRepository->save($user);

        $codes = $this->recoveryCodeGenerator->generateAndStore($user);
        $revokedCount = $this->revokeOtherSessions($user, $command->currentSessionId);

        $command->setResponse(new ConfirmTwoFactorCommandResponse($codes));
        $this->publishEvents($user, $revokedCount);
    }

    private function resolveUser(string $email): User
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user instanceof User) {
            throw new UnauthorizedHttpException('Bearer', 'Authentication required.');
        }

        if ($user->getTwoFactorSecret() === null) {
            throw new UnauthorizedHttpException('Bearer', 'Two-factor setup not initiated.');
        }

        return $user;
    }

    private function verifyTotpOrFail(User $user, string $code): void
    {
        $this->twoFactorCodeVerifier->verifyTotpOrFail($user, $code);
    }

    /**
     * @psalm-return int<0, max>
     */
    private function revokeOtherSessions(User $user, string $currentSessionId): int
    {
        $sessions = $this->authSessionRepository->findByUserId($user->getId());
        $revokedCount = 0;

        foreach ($sessions as $session) {
            if ($this->shouldRevoke($session, $currentSessionId)) {
                $session->revoke();
                $this->authSessionRepository->save($session);
                $revokedCount++;
            }
        }

        return $revokedCount;
    }

    private function shouldRevoke(AuthSession $session, string $currentSessionId): bool
    {
        return $session->getId() !== $currentSessionId && !$session->isRevoked();
    }

    private function publishEvents(User $user, int $revokedCount): void
    {
        $this->events->publishEnabled(
            $user->getId(),
            $user->getEmail()
        );
        $this->sessionEvents->publishAllSessionsRevoked(
            $user->getId(),
            'two_factor_enabled',
            $revokedCount
        );
    }
}
