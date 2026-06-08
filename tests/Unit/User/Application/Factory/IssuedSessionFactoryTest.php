<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\IssuedSession;
use App\User\Application\Factory\AccessTokenFactoryInterface;
use App\User\Application\Factory\AuthTokenFactoryInterface;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Application\Factory\IssuedSessionFactory;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\AuthSessionFactoryInterface;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class IssuedSessionFactoryTest extends UnitTestCase
{
    private const STANDARD_TTL = 900;
    private const REMEMBER_ME_TTL = 2592000;

    private AuthSessionRepositoryInterface&MockObject $authSessionRepo;
    private AuthRefreshTokenRepositoryInterface&MockObject $refreshTokenRepo;
    private AccessTokenFactoryInterface&MockObject $accessTokenFactory;
    private AuthTokenFactoryInterface&MockObject $authTokenFactory;
    private AuthSessionFactoryInterface&MockObject $sessionFactory;
    private IdFactoryInterface&MockObject $idFactory;
    private LoggerInterface&MockObject $logger;
    private IssuedSessionFactory $issuer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initMocks();
        $this->issuer = new IssuedSessionFactory(
            $this->authSessionRepo,
            $this->refreshTokenRepo,
            $this->accessTokenFactory,
            $this->authTokenFactory,
            $this->sessionFactory,
            $this->idFactory,
            $this->logger,
            self::STANDARD_TTL,
            self::REMEMBER_ME_TTL,
        );
    }

    public function testIssueWithoutRememberMe(): void
    {
        [$user, $session, $accessToken, $refreshToken, $issuedAt] =
            $this->arrangeIssueFixtures(false);

        $result = $this->issuer->create(
            $user,
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            false,
            $issuedAt
        );

        $this->assertInstanceOf(IssuedSession::class, $result);
        $this->assertSame($session->getId(), $result->sessionId);
        $this->assertSame($accessToken, $result->accessToken);
        $this->assertSame($refreshToken, $result->refreshToken);
    }

    public function testIssueWithRememberMe(): void
    {
        [$user, , , , $issuedAt] =
            $this->arrangeIssueFixtures(true);

        $result = $this->issuer->create(
            $user,
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            true,
            $issuedAt
        );

        $this->assertInstanceOf(IssuedSession::class, $result);
    }

    public function testCreateRollsBackSessionAndRefreshTokenWhenAccessTokenFails(): void
    {
        $failure = new RuntimeException('JWT signing failed.');
        [$user, $issuedAt] = $this->arrangeAccessTokenFailureRollback($failure);

        $this->expectExceptionObject($failure);

        $this->issuer->create(
            $user,
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            false,
            $issuedAt
        );
    }

    public function testCreateLogsRollbackFailureAndPreservesIssuanceFailure(): void
    {
        $failure = new RuntimeException('JWT signing failed.');
        $rollbackFailure = new RuntimeException('Refresh token cleanup failed.');
        [$user, $issuedAt] = $this->arrangeAccessTokenFailureRollbackFailure(
            $failure,
            $rollbackFailure
        );

        $this->expectExceptionObject($failure);

        $this->issuer->create(
            $user,
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            false,
            $issuedAt
        );
    }

    public function testCreateLogsLookupAndSessionRollbackFailures(): void
    {
        $failure = new RuntimeException('JWT signing failed.');
        $lookupFailure = new RuntimeException('Refresh token lookup failed.');
        $sessionDeleteFailure = new RuntimeException('Session cleanup failed.');
        [$user, $issuedAt] = $this->arrangeAccessTokenFailureLookupAndSessionRollbackFailure(
            $failure,
            $lookupFailure,
            $sessionDeleteFailure
        );

        $this->expectExceptionObject($failure);

        $this->issuer->create(
            $user,
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            false,
            $issuedAt
        );
    }

    /**
     * @return array{User&MockObject, DateTimeImmutable}
     */
    private function arrangeAccessTokenFailureRollback(RuntimeException $failure): array
    {
        [$userId, $sessionId, $issuedAt, $session, $user] = $this->createFailureFixtures();
        $refreshToken = $this->createMock(AuthRefreshToken::class);

        $this->arrangeSessionCreation($sessionId, $userId, $issuedAt, false, $session);
        $this->arrangeFailingAccessTokenCreation($userId, $refreshToken, $failure);
        $this->expectSessionRollback($sessionId, $session, $refreshToken);

        return [$user, $issuedAt];
    }

    /**
     * @return array{User&MockObject, DateTimeImmutable}
     */
    private function arrangeAccessTokenFailureRollbackFailure(
        RuntimeException $failure,
        RuntimeException $rollbackFailure
    ): array {
        [$userId, $sessionId, $issuedAt, $session, $user] = $this->createFailureFixtures();
        $refreshToken = $this->createMock(AuthRefreshToken::class);
        $secondRefreshToken = $this->createMock(AuthRefreshToken::class);

        $this->arrangeSessionCreation($sessionId, $userId, $issuedAt, false, $session);
        $this->arrangeFailingAccessTokenCreation($userId, $refreshToken, $failure);
        $this->expectFailingTokenRollback(
            $sessionId,
            $session,
            $refreshToken,
            $secondRefreshToken,
            $rollbackFailure
        );
        $this->expectRollbackFailureLog($sessionId, $failure, [$rollbackFailure]);

        return [$user, $issuedAt];
    }

    /**
     * @return array{User&MockObject, DateTimeImmutable}
     */
    private function arrangeAccessTokenFailureLookupAndSessionRollbackFailure(
        RuntimeException $failure,
        RuntimeException $lookupFailure,
        RuntimeException $sessionDeleteFailure
    ): array {
        [$userId, $sessionId, $issuedAt, $session, $user] = $this->createFailureFixtures();
        $refreshToken = $this->createMock(AuthRefreshToken::class);

        $this->arrangeSessionCreation($sessionId, $userId, $issuedAt, false, $session);
        $this->arrangeFailingAccessTokenCreation($userId, $refreshToken, $failure);
        $this->expectFailingLookupAndSessionRollback(
            $sessionId,
            $session,
            $lookupFailure,
            $sessionDeleteFailure
        );
        $this->expectRollbackFailureLog(
            $sessionId,
            $failure,
            [$lookupFailure, $sessionDeleteFailure]
        );

        return [$user, $issuedAt];
    }

    /**
     * @return array{
     *     string,
     *     string,
     *     DateTimeImmutable,
     *     AuthSession&MockObject,
     *     User&MockObject
     * }
     */
    private function createFailureFixtures(): array
    {
        $userId = $this->faker->uuid();
        $sessionId = $this->faker->uuid();
        $issuedAt = new DateTimeImmutable();
        $session = $this->createMock(AuthSession::class);
        $user = $this->createMock(User::class);

        $user->method('getId')->willReturn($userId);
        $session->method('getId')->willReturn($sessionId);

        return [$userId, $sessionId, $issuedAt, $session, $user];
    }

    private function arrangeFailingAccessTokenCreation(
        string $userId,
        AuthRefreshToken&MockObject $refreshToken,
        RuntimeException $failure
    ): void {
        $this->authTokenFactory->method('generateOpaqueToken')->willReturn($this->faker->sha256());
        $this->authTokenFactory->method('createRefreshToken')->willReturn($refreshToken);
        $this->authTokenFactory->method('buildJwtPayload')->willReturn(['sub' => $userId]);
        $this->accessTokenFactory->method('create')->willThrowException($failure);
    }

    private function expectSessionRollback(
        string $sessionId,
        AuthSession&MockObject $session,
        AuthRefreshToken&MockObject $refreshToken
    ): void {
        $this->refreshTokenRepo->expects($this->once())
            ->method('findBySessionId')
            ->with($sessionId)
            ->willReturn([$refreshToken]);
        $this->refreshTokenRepo->expects($this->once())->method('delete')->with($refreshToken);
        $this->authSessionRepo->expects($this->once())->method('delete')->with($session);
    }

    private function expectFailingTokenRollback(
        string $sessionId,
        AuthSession&MockObject $session,
        AuthRefreshToken&MockObject $refreshToken,
        AuthRefreshToken&MockObject $secondRefreshToken,
        RuntimeException $rollbackFailure
    ): void {
        $this->refreshTokenRepo->expects($this->once())
            ->method('findBySessionId')
            ->with($sessionId)
            ->willReturn([$refreshToken, $secondRefreshToken]);
        $this->refreshTokenRepo->expects($this->exactly(2))
            ->method('delete')
            ->willReturnCallback(
                static function (AuthRefreshToken $token) use (
                    $refreshToken,
                    $rollbackFailure
                ): void {
                    if ($token === $refreshToken) {
                        throw $rollbackFailure;
                    }
                }
            );
        $this->authSessionRepo->expects($this->once())->method('delete')->with($session);
    }

    /**
     * @param list<RuntimeException> $rollbackFailures
     */
    private function expectRollbackFailureLog(
        string $sessionId,
        RuntimeException $failure,
        array $rollbackFailures
    ): void {
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Session issuance rollback failed',
                $this->identicalTo($this->rollbackFailureLogContext(
                    $sessionId,
                    $failure,
                    $rollbackFailures
                ))
            );
    }

    /**
     * @param list<RuntimeException> $rollbackFailures
     *
     * @return array{
     *     session_id: string,
     *     issuance_exception_class: class-string<RuntimeException>,
     *     rollback_failure_count: int,
     *     rollback_exception_classes: list<class-string<RuntimeException>>,
     *     rollback_exception_messages: list<string>,
     *     rollback_exceptions: list<RuntimeException>,
     *     exception: RuntimeException
     * }
     */
    private function rollbackFailureLogContext(
        string $sessionId,
        RuntimeException $failure,
        array $rollbackFailures
    ): array {
        return [
            'session_id' => $sessionId,
            'issuance_exception_class' => $failure::class,
            'rollback_failure_count' => count($rollbackFailures),
            'rollback_exception_classes' => $this->exceptionClasses($rollbackFailures),
            'rollback_exception_messages' => $this->exceptionMessages($rollbackFailures),
            'rollback_exceptions' => $rollbackFailures,
            'exception' => $rollbackFailures[0],
        ];
    }

    /**
     * @param list<RuntimeException> $exceptions
     *
     * @return list<class-string<RuntimeException>>
     */
    private function exceptionClasses(array $exceptions): array
    {
        return array_map(
            static fn (RuntimeException $exception): string => $exception::class,
            $exceptions
        );
    }

    /**
     * @param list<RuntimeException> $exceptions
     *
     * @return list<string>
     */
    private function exceptionMessages(array $exceptions): array
    {
        return array_map(
            static fn (RuntimeException $exception): string => $exception->getMessage(),
            $exceptions
        );
    }

    private function expectFailingLookupAndSessionRollback(
        string $sessionId,
        AuthSession&MockObject $session,
        RuntimeException $lookupFailure,
        RuntimeException $sessionDeleteFailure
    ): void {
        $this->refreshTokenRepo->expects($this->once())
            ->method('findBySessionId')
            ->with($sessionId)
            ->willThrowException($lookupFailure);
        $this->refreshTokenRepo->expects($this->never())->method('delete');
        $this->authSessionRepo->expects($this->once())
            ->method('delete')
            ->with($session)
            ->willThrowException($sessionDeleteFailure);
    }

    /**
     * @return array{
     *     User&MockObject,
     *     AuthSession&MockObject,
     *     string, string,
     *     DateTimeImmutable
     * }
     */
    private function arrangeIssueFixtures(bool $rememberMe): array
    {
        $uid = $this->faker->uuid();
        $sid = $this->faker->uuid();
        $refresh = $this->faker->sha256();
        $access = $this->faker->sha256();
        $issuedAt = new DateTimeImmutable();

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($uid);
        $session = $this->createMock(AuthSession::class);
        $session->method('getId')->willReturn($sid);

        $this->arrangeSessionCreation($sid, $uid, $issuedAt, $rememberMe, $session);
        $this->arrangeTokenGeneration($uid, $refresh, $access);

        return [$user, $session, $access, $refresh, $issuedAt];
    }

    private function arrangeSessionCreation(
        string $sessionId,
        string $userId,
        DateTimeImmutable $issuedAt,
        bool $rememberMe,
        AuthSession&MockObject $session
    ): void {
        $ttl = $rememberMe ? self::REMEMBER_ME_TTL : self::STANDARD_TTL;
        $this->idFactory->method('create')->willReturn($sessionId);
        $expected = $issuedAt->modify("+{$ttl} seconds");
        $cb = static function (DateTimeImmutable $exp) use ($expected): bool {
            return $exp->getTimestamp() === $expected->getTimestamp();
        };

        $this->sessionFactory->method('create')
            ->with(
                $sessionId,
                $userId,
                $this->anything(),
                $this->anything(),
                $issuedAt,
                $this->callback($cb),
                $rememberMe
            )
            ->willReturn($session);
        $this->authSessionRepo->method('save');
    }

    private function arrangeTokenGeneration(
        string $userId,
        string $refreshToken,
        string $accessToken
    ): void {
        $this->authTokenFactory->method('generateOpaqueToken')
            ->willReturn($refreshToken);
        $entity = $this->createMock(AuthRefreshToken::class);
        $this->authTokenFactory->method('createRefreshToken')
            ->willReturn($entity);
        $this->authTokenFactory->method('buildJwtPayload')
            ->willReturn(['sub' => $userId]);
        $this->refreshTokenRepo->method('save');
        $this->accessTokenFactory->method('create')
            ->willReturn($accessToken);
    }

    private function initMocks(): void
    {
        $m = fn (string $c) => $this->createMock($c);
        $this->authSessionRepo = $m(AuthSessionRepositoryInterface::class);
        $this->refreshTokenRepo = $m(AuthRefreshTokenRepositoryInterface::class);
        $this->accessTokenFactory = $m(AccessTokenFactoryInterface::class);
        $this->authTokenFactory = $m(AuthTokenFactoryInterface::class);
        $this->sessionFactory = $m(AuthSessionFactoryInterface::class);
        $this->idFactory = $m(IdFactoryInterface::class);
        $this->logger = $m(LoggerInterface::class);
    }
}
