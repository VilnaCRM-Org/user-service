<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\DTO\IssuedSession;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\AuthSessionFactoryInterface;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @psalm-api
 */
final readonly class IssuedSessionFactory implements IssuedSessionFactoryInterface
{
    public function __construct(
        private AuthSessionRepositoryInterface $authSessionRepository,
        private AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository,
        private AccessTokenFactoryInterface $accessTokenFactory,
        private AuthTokenFactoryInterface $authTokenFactory,
        private AuthSessionFactoryInterface $authSessionFactory,
        private IdFactoryInterface $idFactory,
        private LoggerInterface $logger,
        private int $standardSessionTtlSeconds = 900,
        private int $rememberMeSessionTtlSeconds = 2592000,
    ) {
    }

    #[\Override]
    public function create(
        User $user,
        string $ipAddress,
        string $userAgent,
        bool $rememberMe,
        DateTimeImmutable $issuedAt
    ): IssuedSession {
        $session = $this->createSession($user, $ipAddress, $userAgent, $rememberMe, $issuedAt);
        $this->authSessionRepository->save($session);

        try {
            return $this->issueTokens($session, $user, $issuedAt);
        } catch (Throwable $exception) {
            $rollbackFailures = $this->rollbackSession($session);

            if ($rollbackFailures !== []) {
                $this->logRollbackFailures($session, $exception, $rollbackFailures);
            }

            throw $exception;
        }
    }

    private function createSession(
        User $user,
        string $ipAddress,
        string $userAgent,
        bool $rememberMe,
        DateTimeImmutable $issuedAt
    ): AuthSession {
        $ttlSeconds = $rememberMe
            ? $this->rememberMeSessionTtlSeconds
            : $this->standardSessionTtlSeconds;

        return $this->authSessionFactory->create(
            $this->idFactory->create(),
            $user->getId(),
            $ipAddress,
            $userAgent,
            $issuedAt,
            $issuedAt->modify(sprintf('+%d seconds', $ttlSeconds)),
            $rememberMe
        );
    }

    private function issueTokens(
        AuthSession $session,
        User $user,
        DateTimeImmutable $issuedAt
    ): IssuedSession {
        $refreshToken = $this->authTokenFactory->generateOpaqueToken();
        $this->authRefreshTokenRepository->save($this->authTokenFactory->createRefreshToken(
            $session->getId(),
            $refreshToken,
            $issuedAt
        ));
        $accessToken = $this->accessTokenFactory->create(
            $this->authTokenFactory->buildJwtPayload($user, $session->getId(), $issuedAt)
        );

        return new IssuedSession($session->getId(), $accessToken, $refreshToken);
    }

    /**
     * @return list<Throwable>
     */
    private function rollbackSession(AuthSession $session): array
    {
        $failures = [];

        try {
            $tokens = $this->authRefreshTokenRepository->findBySessionId($session->getId());

            foreach ($tokens as $token) {
                try {
                    $this->authRefreshTokenRepository->delete($token);
                } catch (Throwable $exception) {
                    $failures[] = $exception;
                }
            }
        } catch (Throwable $exception) {
            $failures[] = $exception;
        }

        try {
            $this->authSessionRepository->delete($session);
        } catch (Throwable $exception) {
            $failures[] = $exception;
        }

        return $failures;
    }

    /**
     * @param list<Throwable> $rollbackFailures
     */
    private function logRollbackFailures(
        AuthSession $session,
        Throwable $issuanceException,
        array $rollbackFailures
    ): void {
        $this->logger->error('Session issuance rollback failed', [
            'session_id' => $session->getId(),
            'issuance_exception_class' => $issuanceException::class,
            'rollback_failure_count' => count($rollbackFailures),
            'rollback_exception_classes' => array_map(
                static fn (Throwable $exception): string => $exception::class,
                $rollbackFailures
            ),
            'rollback_exception_messages' => array_map(
                static fn (Throwable $exception): string => $exception->getMessage(),
                $rollbackFailures
            ),
            'rollback_exceptions' => $rollbackFailures,
            'exception' => $rollbackFailures[0],
        ]);
    }
}
