<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\RefreshTokenCommand;
use App\User\Application\Factory\AuthTokenFactoryInterface;
use App\User\Application\Service\RefreshTokenEventPublisherInterface;
use App\User\Domain\Contract\AccessTokenGeneratorInterface;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class RefreshTokenCommandHandler implements
    CommandHandlerInterface
{
    private const DEFAULT_GRACE_WINDOW_SECONDS = 60;

    public function __construct(
        private AuthRefreshTokenRepositoryInterface $refreshTokenRepository,
        private AuthSessionRepositoryInterface $authSessionRepository,
        private UserRepositoryInterface $userRepository,
        private AccessTokenGeneratorInterface $accessTokenGenerator,
        private AuthTokenFactoryInterface $authTokenFactory,
        private RefreshTokenEventPublisherInterface $eventPublisher,
        private int $refreshTokenGraceWindowSeconds = self::DEFAULT_GRACE_WINDOW_SECONDS,
    ) {
    }

    public function __invoke(RefreshTokenCommand $command): void
    {
        $oldToken = $this->resolveRefreshToken($command->refreshToken);
        $session = $this->resolveSession($oldToken->getSessionId());
        $user = $this->resolveUser($session->getUserId());
        $currentTime = new DateTimeImmutable();

        if ($this->handleIfAlreadyRotated($oldToken, $session, $user, $command, $currentTime)) {
            return;
        }

        $this->rotateActiveToken($oldToken, $user, $session, $command, $currentTime);
    }

    private function resolveRefreshToken(
        string $plainToken
    ): AuthRefreshToken {
        $hash = hash('sha256', $plainToken);
        $token = $this->refreshTokenRepository->findByTokenHash($hash);

        if (!$token instanceof AuthRefreshToken) {
            $this->throwUnauthorized();
        }

        if ($token->isExpired() || $token->isRevoked()) {
            $this->throwUnauthorized();
        }

        return $token;
    }

    private function handleIfAlreadyRotated(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        User $user,
        RefreshTokenCommand $command,
        DateTimeImmutable $currentTime
    ): bool {
        if ($this->tryHandleRotatedToken($oldToken, $session, $user, $command, $currentTime)) {
            return true;
        }

        return $this->tryHandleConcurrentRotation(
            $oldToken->getTokenHash(),
            $session,
            $user,
            $command,
            $currentTime,
            $command->refreshToken
        );
    }

    private function handleRotatedToken(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        User $user,
        RefreshTokenCommand $command,
        DateTimeImmutable $currentTime
    ): void {
        if ($oldToken->isWithinGracePeriod(
            $currentTime,
            $this->refreshTokenGraceWindowSeconds
        )) {
            $this->handleGraceWindowReuse(
                $oldToken,
                $session,
                $user,
                $command,
                $currentTime
            );

            return;
        }

        $this->handleTheftDetection(
            $oldToken,
            $session,
            $user,
            'grace_period_expired'
        );
    }

    private function handleGraceWindowReuse(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        User $user,
        RefreshTokenCommand $command,
        DateTimeImmutable $currentTime
    ): void {
        $this->assertGraceWindowIsEligible(
            $oldToken,
            $session,
            $user,
            $currentTime
        );

        $oldToken->markGraceUsed();

        $this->setResponseWithNewTokens($command, $user, $session);
        $this->publishRotatedEvent($session, $user);
    }

    private function handleTheftDetection(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        User $user,
        string $reason
    ): never {
        $this->revokeSessionAndTokens($oldToken, $session);
        $this->publishTheftDetectedEvent($session, $user, $reason);

        $this->throwUnauthorized();
    }

    private function revokeSessionAndTokens(
        AuthRefreshToken $oldToken,
        AuthSession $session
    ): void {
        if (!$session->isRevoked()) {
            $session->revoke();
            $this->authSessionRepository->save($session);
        }

        $tokens = $this->refreshTokenRepository->findBySessionId(
            $session->getId()
        );
        if ($tokens === []) {
            $tokens = [$oldToken];
        }

        foreach ($tokens as $token) {
            if ($token->isRevoked()) {
                continue;
            }

            $token->revoke();
            $this->refreshTokenRepository->save($token);
        }
    }

    private function resolveSession(string $sessionId): AuthSession
    {
        $session = $this->authSessionRepository->findById($sessionId);
        if (!$session instanceof AuthSession) {
            $this->throwUnauthorized();
        }
        if ($session->isRevoked() || $session->isExpired()) {
            $this->throwUnauthorized();
        }

        return $session;
    }

    private function tryHandleRotatedToken(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        User $user,
        RefreshTokenCommand $command,
        DateTimeImmutable $currentTime
    ): bool {
        if (!$oldToken->isRotated()) {
            return false;
        }

        $this->handleRotatedToken($oldToken, $session, $user, $command, $currentTime);

        return true;
    }

    private function tryHandleConcurrentRotation(
        string $tokenHash,
        AuthSession $session,
        User $user,
        RefreshTokenCommand $command,
        DateTimeImmutable $currentTime,
        string $plainToken
    ): bool {
        $wasRotated = $this->refreshTokenRepository->markAsRotatedIfActive(
            $tokenHash,
            $currentTime
        );
        if ($wasRotated) {
            return false;
        }

        $latestToken = $this->resolveRefreshToken($plainToken);
        if (!$latestToken->isRotated()) {
            $this->throwUnauthorized();
        }

        $this->handleRotatedToken(
            $latestToken,
            $session,
            $user,
            $command,
            $currentTime
        );

        return true;
    }

    private function rotateActiveToken(
        AuthRefreshToken $oldToken,
        User $user,
        AuthSession $session,
        RefreshTokenCommand $command,
        DateTimeImmutable $currentTime
    ): void {
        $oldToken->markAsRotated($currentTime);
        $this->setResponseWithNewTokens($command, $user, $session);
        $this->publishRotatedEvent($session, $user);
    }

    private function setResponseWithNewTokens(
        RefreshTokenCommand $command,
        User $user,
        AuthSession $session
    ): void {
        $issuedAt = new DateTimeImmutable();

        $newRefreshPlain = $this->authTokenFactory->generateOpaqueToken();
        $this->refreshTokenRepository->save(
            $this->authTokenFactory->createRefreshToken(
                $session->getId(),
                $newRefreshPlain,
                $issuedAt
            )
        );

        $accessToken = $this->accessTokenGenerator->generate(
            $this->authTokenFactory->buildJwtPayload($user, $session->getId(), $issuedAt)
        );

        $command->setResponse(
            $this->authTokenFactory->createRefreshTokenResponse(
                $accessToken,
                $newRefreshPlain
            )
        );
    }

    private function assertGraceWindowIsEligible(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        User $user,
        DateTimeImmutable $currentTime
    ): void {
        if ($oldToken->isGraceUsed()) {
            $this->detectDoubleGraceUse($oldToken, $session, $user);
        }

        $graceWindowStartedAt = $currentTime->setTimestamp(
            $currentTime->getTimestamp() - $this->refreshTokenGraceWindowSeconds
        );
        $wasGraceConsumed = $this->refreshTokenRepository
            ->markGraceUsedIfEligible(
                $oldToken->getTokenHash(),
                $graceWindowStartedAt,
                $currentTime
            );
        if ($wasGraceConsumed) {
            return;
        }

        $this->detectDoubleGraceUse($oldToken, $session, $user);
    }

    private function detectDoubleGraceUse(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        User $user
    ): never {
        $this->handleTheftDetection($oldToken, $session, $user, 'double_grace_use');
    }

    private function resolveUser(string $userId): User
    {
        $user = $this->userRepository->findById($userId);
        if (!$user instanceof User) {
            $this->throwUnauthorized();
        }

        return $user;
    }

    private function publishRotatedEvent(
        AuthSession $session,
        User $user
    ): void {
        $this->eventPublisher->publishRotated(
            $session->getId(),
            $user->getId()
        );
    }

    private function publishTheftDetectedEvent(
        AuthSession $session,
        User $user,
        string $reason
    ): void {
        $this->eventPublisher->publishTheftDetected(
            $session->getId(),
            $user->getId(),
            $session->getIpAddress(),
            $reason
        );
    }

    private function throwUnauthorized(): never
    {
        throw new UnauthorizedHttpException(
            'Bearer',
            'Invalid refresh token.'
        );
    }
}
