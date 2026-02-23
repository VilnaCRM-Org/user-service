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
        $this->authRefreshTokenRepository = $this->createMock(
            AuthRefreshTokenRepositoryInterface::class
        );
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
        [$user, $ipAddress, $userAgent, $issuedAt, $refreshToken, $accessToken]
            = $this->createIssueParams();
        $capture = $this->setupSessionCapture();
        $this->setupStandardSessionMocks($refreshToken, $accessToken, $user);

        $result = $this->service->issue($user, $ipAddress, $userAgent, false, $issuedAt);

        $this->assertStandardSessionResult(
            $result,
            $capture->session,
            $user,
            $accessToken,
            $refreshToken,
            $ipAddress,
            $userAgent,
            $issuedAt
        );
    }

    public function testIssueWithRememberMeSession(): void
    {
        [$user, $ipAddress, $userAgent, $issuedAt, $refreshToken, $accessToken]
            = $this->createIssueParams();
        $capture = $this->setupSessionCapture();
        $this->setupBasicSessionMocks($refreshToken, $accessToken, $user);

        $result = $this->service->issue($user, $ipAddress, $userAgent, true, $issuedAt);

        $this->assertInstanceOf(IssuedSession::class, $result);
        $this->assertInstanceOf(AuthSession::class, $capture->session);
        $this->assertTrue($capture->session->isRememberMe());
        $this->assertEquals(
            $issuedAt->modify(sprintf('+%d seconds', self::REMEMBER_ME_SESSION_TTL_SECONDS)),
            $capture->session->getExpiresAt()
        );
    }

    public function testIssuePassesSessionIdToRefreshTokenFactory(): void
    {
        $user = $this->createUser();
        $issuedAt = new DateTimeImmutable();
        $refreshToken = $this->faker->sha256();

        $sessionCapture = $this->setupSessionCapture();
        $this->authTokenFactory->method('generateOpaqueToken')->willReturn($refreshToken);
        $tokenCapture = $this->setupCaptureCreateRefreshToken();
        $this->authTokenFactory->method('buildJwtPayload')->willReturn([]);
        $this->accessTokenGenerator->method('generate')->willReturn($this->faker->sha256());

        $result = $this->service->issue(
            $user,
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            false,
            $issuedAt
        );

        $this->assertSame($sessionCapture->session->getId(), $tokenCapture->sessionId);
        $this->assertSame($refreshToken, $tokenCapture->refreshToken);
        $this->assertSame($sessionCapture->session->getId(), $result->sessionId);
    }

    public function testIssuePassesUserAndSessionIdToBuildJwtPayload(): void
    {
        $user = $this->createUser();
        $issuedAt = new DateTimeImmutable();

        $sessionCapture = $this->setupSessionCapture();
        $this->authTokenFactory->method('generateOpaqueToken')->willReturn($this->faker->sha256());
        $this->authTokenFactory->method('createRefreshToken')
            ->willReturn($this->createMock(AuthRefreshToken::class));
        $jwtCapture = $this->setupCaptureBuildJwtPayload();
        $this->accessTokenGenerator->method('generate')->willReturn($this->faker->sha256());

        $this->service->issue(
            $user,
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            false,
            $issuedAt
        );

        $this->assertSame($user, $jwtCapture->user);
        $this->assertSame($sessionCapture->session->getId(), $jwtCapture->sessionId);
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

    /**
     * @return array{User, string, string, DateTimeImmutable, string, string}
     */
    private function createIssueParams(): array
    {
        return [
            $this->createUser(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            new DateTimeImmutable(),
            $this->faker->sha256(),
            $this->faker->sha256(),
        ];
    }

    private function setupSessionCapture(): \stdClass
    {
        $capture = new \stdClass();
        $capture->session = null;
        $this->authSessionRepository->expects($this->once())
            ->method('save')
            ->willReturnCallback(
                static function (AuthSession $session) use ($capture): void {
                    $capture->session = $session;
                }
            );

        return $capture;
    }

    private function setupStandardSessionMocks(
        string $refreshToken,
        string $accessToken,
        User $user
    ): void {
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
    }

    private function setupBasicSessionMocks(
        string $refreshToken,
        string $accessToken,
        User $user
    ): void {
        $this->authTokenFactory->method('generateOpaqueToken')->willReturn($refreshToken);
        $this->authTokenFactory->method('createRefreshToken')
            ->willReturn($this->createMock(AuthRefreshToken::class));
        $this->authTokenFactory->method('buildJwtPayload')
            ->willReturn(['sub' => $user->getId()]);
        $this->accessTokenGenerator->method('generate')->willReturn($accessToken);
    }

    private function setupCaptureCreateRefreshToken(): \stdClass
    {
        $capture = new \stdClass();
        $capture->sessionId = null;
        $capture->refreshToken = null;
        $this->authTokenFactory->expects($this->once())
            ->method('createRefreshToken')
            ->willReturnCallback(
                function (
                    string $sessionId,
                    string $token
                ) use ($capture): AuthRefreshToken {
                    $capture->sessionId = $sessionId;
                    $capture->refreshToken = $token;

                    return $this->createMock(AuthRefreshToken::class);
                }
            );

        return $capture;
    }

    private function setupCaptureBuildJwtPayload(): \stdClass
    {
        $capture = new \stdClass();
        $capture->user = null;
        $capture->sessionId = null;
        $this->authTokenFactory->expects($this->once())
            ->method('buildJwtPayload')
            ->willReturnCallback(
                static function (User $userArg, string $sessionId) use ($capture): array {
                    $capture->user = $userArg;
                    $capture->sessionId = $sessionId;

                    return ['sub' => $userArg->getId()];
                }
            );

        return $capture;
    }

    private function assertStandardSessionResult(
        IssuedSession $result,
        ?AuthSession $capturedSession,
        User $user,
        string $accessToken,
        string $refreshToken,
        string $ipAddress,
        string $userAgent,
        DateTimeImmutable $issuedAt
    ): void {
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
}
