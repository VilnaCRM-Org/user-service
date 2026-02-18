<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SignInCommand;
use App\User\Application\CommandHandler\SignInCommandHandler;
use App\User\Application\Factory\AuthTokenFactoryInterface;
use App\User\Domain\Contract\AccessTokenGeneratorInterface;
use App\User\Domain\Contract\AccountLockoutServiceInterface;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\AccountLockedOutEvent;
use App\User\Domain\Event\SignInFailedEvent;
use App\User\Domain\Event\UserSignedInEvent;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Ulid;

final class SignInCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private AuthSessionRepositoryInterface&MockObject $authSessionRepository;
    private AuthRefreshTokenRepositoryInterface&MockObject $authRefreshTokenRepository;
    private PendingTwoFactorRepositoryInterface&MockObject $pendingTwoFactorRepository;
    private PasswordHasherFactoryInterface&MockObject $hasherFactory;
    private AccountLockoutServiceInterface&MockObject $lockoutService;
    private AccessTokenGeneratorInterface&MockObject $accessTokenGenerator;
    private AuthTokenFactoryInterface&MockObject $authTokenFactory;
    private EventBusInterface&MockObject $eventBus;
    private UlidFactory&MockObject $ulidFactory;

    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->authSessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->authRefreshTokenRepository = $this->createMock(AuthRefreshTokenRepositoryInterface::class);
        $this->pendingTwoFactorRepository = $this->createMock(PendingTwoFactorRepositoryInterface::class);
        $this->hasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $this->lockoutService = $this->createMock(AccountLockoutServiceInterface::class);
        $this->accessTokenGenerator = $this->createMock(AccessTokenGeneratorInterface::class);
        $this->authTokenFactory = $this->createMock(AuthTokenFactoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);

        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
    }

    /** @SuppressWarnings(PHPMD.CyclomaticComplexity) */
    public function testInvokeReturnsTokensForUserWithoutTwoFactor(): void
    {
        $email = $this->faker->email();
        $plainPassword = $this->faker->password();
        $storedHash = '$2y$04$r2kNnAQAt5lvP0j3QulPaOFeENrToTdbjG6Qx3ZfLTPW7h0v4kN3y';
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $user = $this->createUser($email, $storedHash);

        $hasher = $this->createMock(PasswordHasherInterface::class);

        $this->lockoutService
            ->expects($this->once())
            ->method('isLocked')
            ->with($email)
            ->willReturn(false);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->hasherFactory
            ->expects($this->once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($hasher);

        $hasher
            ->expects($this->once())
            ->method('verify')
            ->with($storedHash, $plainPassword)
            ->willReturn(true);

        $this->lockoutService
            ->expects($this->once())
            ->method('clearFailures')
            ->with($email);

        $sessionId = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');

        $this->ulidFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($sessionId);

        $testJwtPayload = [
            'sub' => $user->getId(),
            'iss' => 'vilnacrm-user-service',
            'aud' => 'vilnacrm-api',
            'exp' => time() + 900,
            'iat' => time(),
            'nbf' => time(),
            'jti' => 'd2719e4f-d1e8-47b6-bd4b-b637f2c40591',
            'sid' => (string) $sessionId,
            'roles' => ['ROLE_USER'],
        ];

        $opaqueToken = str_repeat('a', 43);

        $refreshToken = new AuthRefreshToken(
            '01ARZ3NDEKTSV4RRFFQ69G5FAW',
            (string) $sessionId,
            $opaqueToken,
            (new DateTimeImmutable())->modify('+1 month')
        );

        $this->authTokenFactory
            ->expects($this->once())
            ->method('generateOpaqueToken')
            ->willReturn($opaqueToken);

        $this->authTokenFactory
            ->expects($this->once())
            ->method('createRefreshToken')
            ->with((string) $sessionId, $opaqueToken, $this->isInstanceOf(DateTimeImmutable::class))
            ->willReturn($refreshToken);

        $this->authTokenFactory
            ->expects($this->once())
            ->method('buildJwtPayload')
            ->with($user, (string) $sessionId, $this->isInstanceOf(DateTimeImmutable::class))
            ->willReturn($testJwtPayload);

        $this->authTokenFactory
            ->expects($this->once())
            ->method('nextEventId')
            ->willReturn('e2a1b3c7-16cc-4242-ac9e-c76d740f5d2f');

        $this->accessTokenGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($this->callback(
                static fn (array $payload): bool => isset($payload['sub'], $payload['iss'], $payload['aud'], $payload['exp'], $payload['iat'], $payload['nbf'], $payload['jti'], $payload['sid'], $payload['roles'])
                    && $payload['sub'] === $user->getId()
                    && $payload['iss'] === 'vilnacrm-user-service'
                    && $payload['aud'] === 'vilnacrm-api'
                    && $payload['jti'] === 'd2719e4f-d1e8-47b6-bd4b-b637f2c40591'
                    && $payload['sid'] === (string) $sessionId
                    && is_int($payload['iat'])
                    && is_int($payload['exp'])
                    && ($payload['exp'] - $payload['iat']) === 900
                    && $payload['roles'] === ['ROLE_USER']
            ))
            ->willReturn('signed-access-token');

        $this->authSessionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (AuthSession $session): bool => $session->getId() === (string) $sessionId
                    && $session->getUserId() === $user->getId()
                    && $session->getIpAddress() === $ipAddress
                    && $session->getUserAgent() === $userAgent
                    && ($session->getExpiresAt()->getTimestamp() - $session->getCreatedAt()->getTimestamp()) === 900
                    && $session->isRememberMe() === false
            ));

        $this->authRefreshTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($refreshToken);

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(static function (UserSignedInEvent $event): bool {
                return $event->twoFactorUsed === false;
            }));

        $handler = new SignInCommandHandler(
            $this->userRepository,
            $this->authSessionRepository,
            $this->authRefreshTokenRepository,
            $this->pendingTwoFactorRepository,
            $this->hasherFactory,
            $this->lockoutService,
            $this->accessTokenGenerator,
            $this->authTokenFactory,
            $this->eventBus,
            $this->ulidFactory,
            dummyPasswordHash: $this->createDummyPasswordHash(),
        );
        $command = new SignInCommand($email, $plainPassword, false, $ipAddress, $userAgent);

        $handler->__invoke($command);

        $this->assertFalse($command->getResponse()->isTwoFactorEnabled());
        $this->assertSame('signed-access-token', $command->getResponse()->getAccessToken());
        $this->assertSame($opaqueToken, $command->getResponse()->getRefreshToken());
        $this->assertSame(43, strlen((string) $command->getResponse()->getRefreshToken()));
    }

    public function testInvokeThrowsUnauthorizedWhenPasswordIsInvalid(): void
    {
        $email = $this->faker->email();
        $plainPassword = $this->faker->password();
        $storedHash = '$2y$04$r2kNnAQAt5lvP0j3QulPaOFeENrToTdbjG6Qx3ZfLTPW7h0v4kN3y';
        $user = $this->createUser($email, $storedHash);

        $hasher = $this->createMock(PasswordHasherInterface::class);

        $this->lockoutService
            ->expects($this->once())
            ->method('isLocked')
            ->with($email)
            ->willReturn(false);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->hasherFactory
            ->expects($this->once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($hasher);

        $hasher
            ->expects($this->once())
            ->method('verify')
            ->with($storedHash, $plainPassword)
            ->willReturn(false);

        $this->lockoutService
            ->expects($this->once())
            ->method('recordFailure')
            ->with($email)
            ->willReturn(false);

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(SignInFailedEvent::class));

        $this->authSessionRepository
            ->expects($this->never())
            ->method('save');

        $this->authRefreshTokenRepository
            ->expects($this->never())
            ->method('save');

        $this->accessTokenGenerator
            ->expects($this->never())
            ->method('generate');

        $handler = new SignInCommandHandler(
            $this->userRepository,
            $this->authSessionRepository,
            $this->authRefreshTokenRepository,
            $this->pendingTwoFactorRepository,
            $this->hasherFactory,
            $this->lockoutService,
            $this->accessTokenGenerator,
            $this->authTokenFactory,
            $this->eventBus,
            $this->ulidFactory,
            dummyPasswordHash: $this->createDummyPasswordHash(),
        );
        $command = new SignInCommand(
            $email,
            $plainPassword,
            false,
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );

        try {
            $handler->__invoke($command);
            $this->fail('Expected UnauthorizedHttpException to be thrown.');
        } catch (UnauthorizedHttpException $exception) {
            $this->assertStringContainsString(
                'Bearer',
                (string) ($exception->getHeaders()['WWW-Authenticate'] ?? '')
            );
            $this->assertSame('Invalid credentials.', $exception->getMessage());
        }
    }

    public function testInvokeCreatesRememberMeSessionWhenRequested(): void
    {
        $email = $this->faker->email();
        $plainPassword = $this->faker->password();
        $storedHash = '$2y$04$r2kNnAQAt5lvP0j3QulPaOFeENrToTdbjG6Qx3ZfLTPW7h0v4kN3y';
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $user = $this->createUser($email, $storedHash);

        $hasher = $this->createMock(PasswordHasherInterface::class);

        $this->lockoutService
            ->expects($this->once())
            ->method('isLocked')
            ->with($email)
            ->willReturn(false);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->hasherFactory
            ->expects($this->once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($hasher);

        $hasher
            ->expects($this->once())
            ->method('verify')
            ->with($storedHash, $plainPassword)
            ->willReturn(true);

        $this->lockoutService
            ->expects($this->once())
            ->method('clearFailures')
            ->with($email);

        $sessionId = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FB0');

        $this->ulidFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($sessionId);

        $opaqueToken = str_repeat('b', 43);
        $refreshToken = new AuthRefreshToken(
            '01ARZ3NDEKTSV4RRFFQ69G5FB1',
            (string) $sessionId,
            $opaqueToken,
            (new DateTimeImmutable())->modify('+1 month')
        );

        $this->authTokenFactory->method('generateOpaqueToken')->willReturn($opaqueToken);
        $this->authTokenFactory->method('createRefreshToken')->willReturn($refreshToken);
        $this->authTokenFactory->method('buildJwtPayload')->willReturn([]);
        $this->authTokenFactory->method('nextEventId')->willReturn('ed15a4b0-e7a2-4959-8c88-c8fc23832a15');

        $this->accessTokenGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('remember-token');

        $this->authSessionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (AuthSession $session): bool => $session->getId() === (string) $sessionId
                    && $session->getUserId() === $user->getId()
                    && $session->getIpAddress() === $ipAddress
                    && $session->getUserAgent() === $userAgent
                    && ($session->getExpiresAt()->getTimestamp() - $session->getCreatedAt()->getTimestamp()) === 2592000
                    && $session->isRememberMe() === true
            ));

        $this->authRefreshTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(AuthRefreshToken::class));

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(static function (UserSignedInEvent $event): bool {
                return $event->twoFactorUsed === false;
            }));

        $handler = $this->createHandler();
        $command = new SignInCommand($email, $plainPassword, true, $ipAddress, $userAgent);

        $handler->__invoke($command);

        $this->assertSame('remember-token', $command->getResponse()->getAccessToken());
        $this->assertSame($opaqueToken, $command->getResponse()->getRefreshToken());
    }

    public function testInvokeReturnsTwoFactorResponseWhenTwoFactorIsEnabled(): void
    {
        $email = $this->faker->email();
        $plainPassword = $this->faker->password();
        $storedHash = '$2y$04$r2kNnAQAt5lvP0j3QulPaOFeENrToTdbjG6Qx3ZfLTPW7h0v4kN3y';
        $user = $this->createUser($email, $storedHash);
        $user->setTwoFactorEnabled(true);

        $hasher = $this->createMock(PasswordHasherInterface::class);

        $this->lockoutService
            ->expects($this->once())
            ->method('isLocked')
            ->with($email)
            ->willReturn(false);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->hasherFactory
            ->expects($this->once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($hasher);

        $hasher
            ->expects($this->once())
            ->method('verify')
            ->with($storedHash, $plainPassword)
            ->willReturn(true);

        $this->lockoutService
            ->expects($this->once())
            ->method('clearFailures')
            ->with($email);

        $pendingSessionId = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FB2');
        $this->ulidFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($pendingSessionId);

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (PendingTwoFactor $pendingTwoFactor): bool => $pendingTwoFactor->getId() === (string) $pendingSessionId
                    && $pendingTwoFactor->getUserId() === $user->getId()
                    && $pendingTwoFactor->getExpiresAt()->getTimestamp() - $pendingTwoFactor->getCreatedAt()->getTimestamp() === 300
                    && $pendingTwoFactor->isRememberMe() === false
            ));

        $this->authSessionRepository
            ->expects($this->never())
            ->method('save');

        $this->authRefreshTokenRepository
            ->expects($this->never())
            ->method('save');

        $this->accessTokenGenerator
            ->expects($this->never())
            ->method('generate');

        $this->eventBus
            ->expects($this->never())
            ->method('publish');

        $handler = new SignInCommandHandler(
            $this->userRepository,
            $this->authSessionRepository,
            $this->authRefreshTokenRepository,
            $this->pendingTwoFactorRepository,
            $this->hasherFactory,
            $this->lockoutService,
            $this->accessTokenGenerator,
            $this->authTokenFactory,
            $this->eventBus,
            $this->ulidFactory,
            dummyPasswordHash: $this->createDummyPasswordHash(),
        );
        $command = new SignInCommand(
            $email,
            $plainPassword,
            false,
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );

        $handler->__invoke($command);

        $this->assertTrue($command->getResponse()->isTwoFactorEnabled());
        $this->assertSame((string) $pendingSessionId, $command->getResponse()->getPendingSessionId());
        $this->assertNull($command->getResponse()->getAccessToken());
        $this->assertNull($command->getResponse()->getRefreshToken());
    }

    public function testInvokeStoresRememberMeInPendingTwoFactorWhenTwoFactorIsEnabled(): void
    {
        $email = $this->faker->email();
        $plainPassword = $this->faker->password();
        $storedHash = '$2y$04$r2kNnAQAt5lvP0j3QulPaOFeENrToTdbjG6Qx3ZfLTPW7h0v4kN3y';
        $user = $this->createUser($email, $storedHash);
        $user->setTwoFactorEnabled(true);

        $hasher = $this->createMock(PasswordHasherInterface::class);

        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->hasherFactory->method('getPasswordHasher')->willReturn($hasher);
        $hasher->method('verify')->willReturn(true);
        $this->lockoutService->method('clearFailures');

        $pendingSessionId = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FB3');
        $this->ulidFactory->method('create')->willReturn($pendingSessionId);

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (PendingTwoFactor $pendingTwoFactor): bool => $pendingTwoFactor->isRememberMe() === true
            ));

        $handler = $this->createHandler();
        $command = new SignInCommand(
            $email,
            $plainPassword,
            true,  // rememberMe = true
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );

        $handler->__invoke($command);

        $this->assertTrue($command->getResponse()->isTwoFactorEnabled());
    }

    public function testInvokeUsesDummyPasswordVerificationWhenUserDoesNotExist(): void
    {
        $email = $this->faker->email();
        $plainPassword = $this->faker->password();

        $hasher = $this->createMock(PasswordHasherInterface::class);

        $this->lockoutService
            ->expects($this->once())
            ->method('isLocked')
            ->with($email)
            ->willReturn(false);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->hasherFactory
            ->expects($this->once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($hasher);

        $hasher
            ->expects($this->once())
            ->method('verify')
            ->with(
                $this->callback(static fn (string $dummyHash): bool => str_starts_with($dummyHash, '$2y$12$')),
                $plainPassword
            )
            ->willReturn(false);

        $this->lockoutService
            ->expects($this->once())
            ->method('recordFailure')
            ->with($email)
            ->willReturn(false);

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(SignInFailedEvent::class));

        $handler = $this->createHandler();
        $command = new SignInCommand(
            $email,
            $plainPassword,
            false,
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );

        $this->expectException(UnauthorizedHttpException::class);
        $handler->__invoke($command);
    }

    public function testInvokeBuildsDummyHashFromPasswordHasherWhenOverrideIsNotProvided(): void
    {
        $email = $this->faker->email();
        $plainPassword = $this->faker->password();
        $computedDummyHash = password_hash('signin-dummy-password', PASSWORD_BCRYPT, ['cost' => 12]);

        $hasher = $this->createMock(PasswordHasherInterface::class);

        $this->lockoutService
            ->expects($this->once())
            ->method('isLocked')
            ->with($email)
            ->willReturn(false);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->hasherFactory
            ->expects($this->exactly(2))
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($hasher);

        $hasher
            ->expects($this->once())
            ->method('hash')
            ->with('signin-dummy-password')
            ->willReturn($computedDummyHash);

        $hasher
            ->expects($this->once())
            ->method('verify')
            ->with($computedDummyHash, $plainPassword)
            ->willReturn(false);

        $this->lockoutService
            ->expects($this->once())
            ->method('recordFailure')
            ->with($email)
            ->willReturn(false);

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(SignInFailedEvent::class));

        $handler = new SignInCommandHandler(
            $this->userRepository,
            $this->authSessionRepository,
            $this->authRefreshTokenRepository,
            $this->pendingTwoFactorRepository,
            $this->hasherFactory,
            $this->lockoutService,
            $this->accessTokenGenerator,
            $this->authTokenFactory,
            $this->eventBus,
            $this->ulidFactory,
        );

        $command = new SignInCommand(
            $email,
            $plainPassword,
            false,
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );

        $this->expectException(UnauthorizedHttpException::class);
        $handler->__invoke($command);
    }

    public function testInvokeThrowsLockedWhenFailureThresholdReached(): void
    {
        $email = $this->faker->email();
        $plainPassword = $this->faker->password();

        $hasher = $this->createMock(PasswordHasherInterface::class);

        $this->lockoutService
            ->expects($this->once())
            ->method('isLocked')
            ->with($email)
            ->willReturn(false);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);

        $this->hasherFactory
            ->expects($this->once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($hasher);

        $hasher
            ->expects($this->once())
            ->method('verify')
            ->willReturn(false);

        $this->lockoutService
            ->expects($this->once())
            ->method('recordFailure')
            ->with($email)
            ->willReturn(true);

        $publishedEvents = [];
        $lockedOutEvent = null;
        $this->eventBus
            ->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(static function (...$events) use (&$publishedEvents, &$lockedOutEvent): void {
                $publishedEvents[] = $events[0]::class;
                if ($events[0] instanceof AccountLockedOutEvent) {
                    $lockedOutEvent = $events[0];
                }
            });

        $handler = $this->createHandler();
        $command = new SignInCommand(
            $email,
            $plainPassword,
            false,
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );

        try {
            $handler->__invoke($command);
            $this->fail('Expected LockedHttpException to be thrown.');
        } catch (LockedHttpException $exception) {
            $this->assertSame(
                [
                    SignInFailedEvent::class,
                    AccountLockedOutEvent::class,
                ],
                $publishedEvents
            );
            $this->assertNotNull($lockedOutEvent);
            $this->assertSame(900, $lockedOutEvent->lockoutDurationSeconds);
            $this->assertSame(20, $lockedOutEvent->failedAttempts);
            $this->assertSame('Account temporarily locked', $exception->getMessage());
            $this->assertSame(0, $exception->getCode());
            $this->assertSame('900', $exception->getHeaders()['Retry-After'] ?? null);
        }
    }

    public function testInvokeThrowsLockedWhenAccountAlreadyLocked(): void
    {
        $email = $this->faker->email();

        $this->lockoutService
            ->expects($this->once())
            ->method('isLocked')
            ->with($email)
            ->willReturn(true);

        $this->userRepository
            ->expects($this->never())
            ->method('findByEmail');

        $this->hasherFactory
            ->expects($this->never())
            ->method('getPasswordHasher');

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(static function (AccountLockedOutEvent $event): bool {
                return $event->lockoutDurationSeconds === 900
                    && $event->failedAttempts === 20;
            }));

        $handler = $this->createHandler();
        $command = new SignInCommand(
            $email,
            $this->faker->password(),
            false,
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );

        try {
            $handler->__invoke($command);
            $this->fail('Expected LockedHttpException to be thrown.');
        } catch (LockedHttpException $exception) {
            $this->assertSame('Account temporarily locked', $exception->getMessage());
            $this->assertSame(0, $exception->getCode());
            $this->assertSame('900', $exception->getHeaders()['Retry-After'] ?? null);
        }
    }

    public function testInvokeNormalizesEmailBeforeLookupAndLockoutChecks(): void
    {
        $rawEmail = '  USER@Example.COM  ';
        $normalizedEmail = 'user@example.com';
        $plainPassword = $this->faker->password();
        $storedHash = '$2y$04$r2kNnAQAt5lvP0j3QulPaOFeENrToTdbjG6Qx3ZfLTPW7h0v4kN3y';
        $user = $this->createUser($normalizedEmail, $storedHash);
        $hasher = $this->createMock(PasswordHasherInterface::class);

        $this->lockoutService
            ->expects($this->once())
            ->method('isLocked')
            ->with($normalizedEmail)
            ->willReturn(false);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($normalizedEmail)
            ->willReturn($user);

        $this->hasherFactory
            ->expects($this->once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($hasher);

        $hasher
            ->expects($this->once())
            ->method('verify')
            ->with($storedHash, $plainPassword)
            ->willReturn(true);

        $this->lockoutService
            ->expects($this->once())
            ->method('clearFailures')
            ->with($normalizedEmail);

        $this->ulidFactory
            ->method('create')
            ->willReturnCallback(static fn () => new Ulid());

        $this->authTokenFactory->method('generateOpaqueToken')->willReturn(str_repeat('c', 43));
        $this->authTokenFactory->method('createRefreshToken')->willReturn(
            new AuthRefreshToken('id', 'sid', str_repeat('c', 43), new DateTimeImmutable('+1 month'))
        );
        $this->authTokenFactory->method('buildJwtPayload')->willReturn([]);
        $this->authTokenFactory->method('nextEventId')->willReturn('event-id');

        $this->accessTokenGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('normalized-email-token');

        $this->authSessionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(AuthSession::class));

        $this->authRefreshTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(AuthRefreshToken::class));

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(static function (UserSignedInEvent $event): bool {
                return $event->twoFactorUsed === false;
            }));

        $handler = $this->createHandler();
        $handler->__invoke(
            new SignInCommand(
                $rawEmail,
                $plainPassword,
                false,
                $this->faker->ipv4(),
                $this->faker->userAgent()
            )
        );
    }

    private function createHandler(): SignInCommandHandler
    {
        return new SignInCommandHandler(
            $this->userRepository,
            $this->authSessionRepository,
            $this->authRefreshTokenRepository,
            $this->pendingTwoFactorRepository,
            $this->hasherFactory,
            $this->lockoutService,
            $this->accessTokenGenerator,
            $this->authTokenFactory,
            $this->eventBus,
            $this->ulidFactory,
            300,
            $this->createDummyPasswordHash(),
        );
    }

    private function createDummyPasswordHash(): string
    {
        return password_hash('signin-dummy-password', PASSWORD_BCRYPT, ['cost' => 12]);
    }

    private function createUser(string $email, string $passwordHash): User
    {
        $user = $this->userFactory->create(
            $email,
            $this->faker->firstName(),
            $passwordHash,
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );

        $user->setPassword($passwordHash);

        return $user;
    }
}
