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
    }
}
