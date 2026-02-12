<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\RefreshTokenCommand;
use App\User\Application\Command\RefreshTokenCommandResponse;
use App\User\Domain\Contract\AccessTokenGeneratorInterface;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\RefreshTokenRotatedEvent;
use App\User\Domain\Event\RefreshTokenTheftDetectedEvent;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateInterval;
use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Factory\UuidFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final readonly class RefreshTokenCommandHandler implements
    CommandHandlerInterface
{
    private const ACCESS_TOKEN_TTL_SECONDS = 900;
    private const JWT_ISSUER = 'vilnacrm-user-service';
    private const JWT_AUDIENCE = 'vilnacrm-api';
    private const DEFAULT_GRACE_WINDOW_SECONDS = 60;

    private DateInterval $refreshTokenTtl;

    public function __construct(
        private AuthRefreshTokenRepositoryInterface $refreshTokenRepository,
        private AuthSessionRepositoryInterface $authSessionRepository,
        private UserRepositoryInterface $userRepository,
        private AccessTokenGeneratorInterface $accessTokenGenerator,
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
        private UlidFactory $ulidFactory,
        private int $refreshTokenGraceWindowSeconds = self::DEFAULT_GRACE_WINDOW_SECONDS,
        string $refreshTokenTtlSpec = 'P1M',
    ) {
        $this->refreshTokenTtl = new DateInterval($refreshTokenTtlSpec);
    }

    public function __invoke(RefreshTokenCommand $command): void
    {
        $oldToken = $this->resolveRefreshToken($command->refreshToken);
        $session = $this->resolveSession($oldToken->getSessionId());
        $user = $this->resolveUser($session->getUserId());

        if ($oldToken->isRotated()) {
            $this->handleRotatedToken($oldToken, $session, $user, $command);

            return;
        }

        $oldToken->markAsRotated();
        $this->refreshTokenRepository->save($oldToken);

        $tokens = $this->issueNewTokens($user, $session);
        $command->setResponse($tokens);

        $this->publishRotatedEvent($session, $user);
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

    private function handleRotatedToken(
        AuthRefreshToken $oldToken,
        AuthSession $session,
        User $user,
        RefreshTokenCommand $command
    ): void {
        if ($oldToken->isWithinGracePeriod(
            new DateTimeImmutable(),
            $this->refreshTokenGraceWindowSeconds
        )) {
            $this->handleGraceWindowReuse($oldToken, $session, $user, $command);

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
        RefreshTokenCommand $command
    ): void {
        if ($oldToken->isGraceUsed()) {
            $this->handleTheftDetection(
                $oldToken,
                $session,
                $user,
                'double_grace_use'
            );
        }

        $oldToken->markGraceUsed();
        $this->refreshTokenRepository->save($oldToken);

        $tokens = $this->issueNewTokens($user, $session);
        $command->setResponse($tokens);

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

        return $session;
    }

    private function resolveUser(string $userId): User
    {
        $user = $this->userRepository->findById($userId);
        if (!$user instanceof User) {
            $this->throwUnauthorized();
        }

        return $user;
    }

    private function issueNewTokens(
        User $user,
        AuthSession $session
    ): RefreshTokenCommandResponse {
        $issuedAt = new DateTimeImmutable();

        $newRefreshPlain = $this->generateOpaqueToken();
        $this->saveNewRefreshToken(
            $session->getId(),
            $newRefreshPlain,
            $issuedAt
        );

        $accessToken = $this->accessTokenGenerator->generate(
            $this->buildJwtPayload($user, $session->getId(), $issuedAt)
        );

        return new RefreshTokenCommandResponse(
            $accessToken,
            $newRefreshPlain
        );
    }

    private function saveNewRefreshToken(
        string $sessionId,
        string $plainToken,
        DateTimeImmutable $issuedAt
    ): void {
        $this->refreshTokenRepository->save(
            new AuthRefreshToken(
                (string) $this->ulidFactory->create(),
                $sessionId,
                $plainToken,
                $issuedAt->add($this->refreshTokenTtl)
            )
        );
    }

    /**
     * @return (int|string|string[])[]
     *
     * @psalm-return array{sub: string, iss: 'vilnacrm-user-service', aud: 'vilnacrm-api', exp: int, iat: int, nbf: int, jti: string, sid: string, roles: list{'ROLE_USER'}}
     */
    private function buildJwtPayload(
        User $user,
        string $sessionId,
        DateTimeImmutable $issuedAt
    ): array {
        $ts = $issuedAt->getTimestamp();

        return [
            'sub' => $user->getId(),
            'iss' => self::JWT_ISSUER,
            'aud' => self::JWT_AUDIENCE,
            'exp' => $ts + self::ACCESS_TOKEN_TTL_SECONDS,
            'iat' => $ts,
            'nbf' => $ts,
            'jti' => (string) $this->uuidFactory->create(),
            'sid' => $sessionId,
            'roles' => ['ROLE_USER'],
        ];
    }

    private function publishRotatedEvent(
        AuthSession $session,
        User $user
    ): void {
        $this->eventBus->publish(
            new RefreshTokenRotatedEvent(
                $session->getId(),
                $user->getId(),
                (string) $this->uuidFactory->create()
            )
        );
    }

    private function publishTheftDetectedEvent(
        AuthSession $session,
        User $user,
        string $reason
    ): void {
        $this->eventBus->publish(
            new RefreshTokenTheftDetectedEvent(
                $session->getId(),
                $user->getId(),
                $session->getIpAddress(),
                $reason,
                (string) $this->uuidFactory->create()
            )
        );
    }

    private function generateOpaqueToken(): string
    {
        return rtrim(
            strtr(base64_encode(random_bytes(32)), '+/', '-_'),
            '='
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
