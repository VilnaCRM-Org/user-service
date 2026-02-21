<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Service;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\AuthTokenFactoryInterface;
use App\User\Application\Service\IssuedSession;
use App\User\Application\Service\SessionIssuanceService;
use App\User\Domain\Contract\AccessTokenGeneratorInterface;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Factory\UlidFactory;

final class SessionIssuanceServiceTest extends UnitTestCase
{
    private const STANDARD_SESSION_TTL_SECONDS = 900;
    private const REMEMBER_ME_SESSION_TTL_SECONDS = 2592000;

    private AuthSessionRepositoryInterface&MockObject $authSessionRepository;
    private AuthRefreshTokenRepositoryInterface&MockObject $authRefreshTokenRepository;
    private AccessTokenGeneratorInterface&MockObject $accessTokenGenerator;
    private AuthTokenFactoryInterface&MockObject $authTokenFactory;
    private UlidFactory $ulidFactory;
    private SessionIssuanceService $service;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->authSessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->authRefreshTokenRepository = $this->createMock(AuthRefreshTokenRepositoryInterface::class);
        $this->accessTokenGenerator = $this->createMock(AccessTokenGeneratorInterface::class);
        $this->authTokenFactory = $this->createMock(AuthTokenFactoryInterface::class);
        $this->ulidFactory = new UlidFactory();
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());

        $this->service = new SessionIssuanceService(
            $this->authSessionRepository,
            $this->authRefreshTokenRepository,
            $this->accessTokenGenerator,
            $this->authTokenFactory,
            $this->ulidFactory
        );
    }

    public function testIssueWithStandardSession(): void
    {
        $user = $this->createUser();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $issuedAt = new DateTimeImmutable();
        $refreshToken = $this->faker->sha256();
        $accessToken = $this->faker->sha256();

        $capturedSession = null;
        $this->authSessionRepository->expects($this->once())
            ->method('save')
            ->willReturnCallback(
                function (AuthSession $session) use (&$capturedSession): void {
                    $capturedSession = $session;
                }
            );

        $this->authTokenFactory->expects($this->once())
            ->method('generateOpaqueToken')
            ->willReturn($refreshToken);

        $refreshTokenEntity = $this->createMock(AuthRefreshToken::class);
        $this->authTokenFactory->expects($this->once())
            ->method('createRefreshToken')
            ->willReturn($refreshTokenEntity);

        $this->authRefreshTokenRepository->expects($this->once())
            ->method('save')
            ->with($refreshTokenEntity);

        $this->authTokenFactory->expects($this->once())
            ->method('buildJwtPayload')
            ->willReturn(['sub' => $user->getId()]);

        $this->accessTokenGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($accessToken);

        $result = $this->service->issue($user, $ipAddress, $userAgent, false, $issuedAt);

        $this->assertInstanceOf(IssuedSession::class, $result);
        $this->assertSame($accessToken, $result->accessToken);
        $this->assertSame($refreshToken, $result->refreshToken);
        $this->assertNotEmpty($result->sessionId);

        $this->assertInstanceOf(AuthSession::class, $capturedSession);
        $this->assertSame($user->getId(), $capturedSession->getUserId());
        $this->assertSame($ipAddress, $capturedSession->getIpAddress());
        $this->assertSame($userAgent, $capturedSession->getUserAgent());
        $this->assertFalse($capturedSession->isRememberMe());
        $this->assertEquals(
            $issuedAt->modify(sprintf('+%d seconds', self::STANDARD_SESSION_TTL_SECONDS)),
            $capturedSession->getExpiresAt()
        );
        $this->assertSame($capturedSession->getId(), $result->sessionId);
    }

    public function testIssueWithRememberMeSession(): void
    {
        $user = $this->createUser();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $issuedAt = new DateTimeImmutable();
        $refreshToken = $this->faker->sha256();
        $accessToken = $this->faker->sha256();

        $capturedSession = null;
        $this->authSessionRepository->expects($this->once())
            ->method('save')
            ->willReturnCallback(
                function (AuthSession $session) use (&$capturedSession): void {
                    $capturedSession = $session;
                }
            );

        $this->authTokenFactory->method('generateOpaqueToken')->willReturn($refreshToken);
        $this->authTokenFactory->method('createRefreshToken')
            ->willReturn($this->createMock(AuthRefreshToken::class));
        $this->authTokenFactory->method('buildJwtPayload')
            ->willReturn(['sub' => $user->getId()]);
        $this->accessTokenGenerator->method('generate')->willReturn($accessToken);

        $result = $this->service->issue($user, $ipAddress, $userAgent, true, $issuedAt);

        $this->assertInstanceOf(IssuedSession::class, $result);
        $this->assertInstanceOf(AuthSession::class, $capturedSession);
        $this->assertTrue($capturedSession->isRememberMe());
        $this->assertEquals(
            $issuedAt->modify(sprintf('+%d seconds', self::REMEMBER_ME_SESSION_TTL_SECONDS)),
            $capturedSession->getExpiresAt()
        );
    }

    public function testIssuePassesSessionIdToRefreshTokenFactory(): void
    {
        $user = $this->createUser();
        $issuedAt = new DateTimeImmutable();
        $refreshToken = $this->faker->sha256();

        $capturedSession = null;
        $this->authSessionRepository->method('save')
            ->willReturnCallback(
                function (AuthSession $session) use (&$capturedSession): void {
                    $capturedSession = $session;
                }
            );

        $this->authTokenFactory->method('generateOpaqueToken')->willReturn($refreshToken);

        $capturedSessionIdInFactory = null;
        $capturedRefreshToken = null;
        $this->authTokenFactory->expects($this->once())
            ->method('createRefreshToken')
            ->willReturnCallback(
                function (
                    string $sessionId,
                    string $token,
                    DateTimeImmutable $issuedAtArg
                ) use (&$capturedSessionIdInFactory, &$capturedRefreshToken): AuthRefreshToken {
                    $capturedSessionIdInFactory = $sessionId;
                    $capturedRefreshToken = $token;
                    return $this->createMock(AuthRefreshToken::class);
                }
            );

        $this->authTokenFactory->method('buildJwtPayload')->willReturn([]);
        $this->accessTokenGenerator->method('generate')->willReturn($this->faker->sha256());

        $result = $this->service->issue(
            $user,
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            false,
            $issuedAt
        );

        $this->assertSame($capturedSession->getId(), $capturedSessionIdInFactory);
        $this->assertSame($refreshToken, $capturedRefreshToken);
        $this->assertSame($capturedSession->getId(), $result->sessionId);
    }

    public function testIssuePassesUserAndSessionIdToBuildJwtPayload(): void
    {
        $user = $this->createUser();
        $issuedAt = new DateTimeImmutable();
        $accessToken = $this->faker->sha256();

        $capturedSession = null;
        $this->authSessionRepository->method('save')
            ->willReturnCallback(
                function (AuthSession $session) use (&$capturedSession): void {
                    $capturedSession = $session;
                }
            );

        $this->authTokenFactory->method('generateOpaqueToken')->willReturn($this->faker->sha256());
        $this->authTokenFactory->method('createRefreshToken')
            ->willReturn($this->createMock(AuthRefreshToken::class));

        $capturedUser = null;
        $capturedSessionId = null;
        $this->authTokenFactory->expects($this->once())
            ->method('buildJwtPayload')
            ->willReturnCallback(
                function (User $userArg, string $sessionId) use (
                    &$capturedUser,
                    &$capturedSessionId,
                    $accessToken
                ): array {
                    $capturedUser = $userArg;
                    $capturedSessionId = $sessionId;
                    return ['sub' => $userArg->getId()];
                }
            );

        $this->accessTokenGenerator->method('generate')->willReturn($accessToken);

        $this->service->issue(
            $user,
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            false,
            $issuedAt
        );

        $this->assertSame($user, $capturedUser);
        $this->assertSame($capturedSession->getId(), $capturedSessionId);
    }

    private function createUser(): User
    {
        return $this->userFactory->create(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
    }
}
