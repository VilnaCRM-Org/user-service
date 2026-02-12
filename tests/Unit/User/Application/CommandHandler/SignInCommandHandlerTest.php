<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SignInCommand;
use App\User\Application\CommandHandler\SignInCommandHandler;
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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

final class SignInCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private AuthSessionRepositoryInterface&MockObject $authSessionRepository;
    private AuthRefreshTokenRepositoryInterface&MockObject $authRefreshTokenRepository;
    private PendingTwoFactorRepositoryInterface&MockObject $pendingTwoFactorRepository;
    private PasswordHasherFactoryInterface&MockObject $hasherFactory;
    private AccountLockoutServiceInterface&MockObject $lockoutService;
    private AccessTokenGeneratorInterface&MockObject $accessTokenGenerator;
    private EventBusInterface&MockObject $eventBus;
    private UuidFactory&MockObject $uuidFactory;
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
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactory::class);
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
        $refreshTokenId = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAW');

        $this->ulidFactory
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($sessionId, $refreshTokenId);

        $jti = Uuid::fromString('d2719e4f-d1e8-47b6-bd4b-b637f2c40591');
        $eventId = Uuid::fromString('e2a1b3c7-16cc-4242-ac9e-c76d740f5d2f');

        $this->uuidFactory
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($jti, $eventId);

        $this->accessTokenGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($this->callback(
                static fn (array $payload): bool => isset($payload['sub'], $payload['iss'], $payload['aud'], $payload['exp'], $payload['iat'], $payload['nbf'], $payload['jti'], $payload['sid'], $payload['roles'])
                    && $payload['sub'] === $user->getId()
                    && $payload['iss'] === 'vilnacrm-user-service'
                    && $payload['aud'] === 'vilnacrm-api'
                    && $payload['jti'] === (string) $jti
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
            ->with($this->callback(
                static fn (AuthRefreshToken $token): bool => $token->getId() === (string) $refreshTokenId
                    && $token->getSessionId() === (string) $sessionId
            ));

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(UserSignedInEvent::class));

        $handler = $this->createHandler();
        $command = new SignInCommand($email, $plainPassword, false, $ipAddress, $userAgent);

        $handler->__invoke($command);

        $this->assertFalse($command->getResponse()->isTwoFactorEnabled());
        $this->assertSame('signed-access-token', $command->getResponse()->getAccessToken());
        $this->assertNotEmpty($command->getResponse()->getRefreshToken());
        $this->assertNotSame(
            $command->getResponse()->getAccessToken(),
            $command->getResponse()->getRefreshToken()
        );
        $this->assertOpaqueTokenFormat($command->getResponse()->getRefreshToken());
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
        $refreshTokenId = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FB1');

        $this->ulidFactory
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($sessionId, $refreshTokenId);

        $jti = Uuid::fromString('ed15a4b0-e7a2-4959-8c88-c8fc23832a15');
        $eventId = Uuid::fromString('31e0c50b-5930-4fcb-aea0-ea6fdce37aeb');

        $this->uuidFactory
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls($jti, $eventId);

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
            ->with($this->isInstanceOf(UserSignedInEvent::class));

        $handler = $this->createHandler();
        $command = new SignInCommand($email, $plainPassword, true, $ipAddress, $userAgent);

        $handler->__invoke($command);

        $this->assertSame('remember-token', $command->getResponse()->getAccessToken());
        $this->assertOpaqueTokenFormat((string) $command->getResponse()->getRefreshToken());
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

        $handler = $this->createHandler();
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
                $this->callback(static fn (string $dummyHash): bool => str_starts_with($dummyHash, '$2y$04$')),
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
        $this->eventBus
            ->expects($this->exactly(2))
            ->method('publish')
            ->willReturnCallback(static function (...$events) use (&$publishedEvents): void {
                $publishedEvents[] = $events[0]::class;
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
            ->with($this->isInstanceOf(AccountLockedOutEvent::class));

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

        $this->uuidFactory
            ->method('create')
            ->willReturnCallback(static fn () => Uuid::v4());

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
            ->with($this->isInstanceOf(UserSignedInEvent::class));

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

    private function assertOpaqueTokenFormat(string $token): void
    {
        $this->assertSame(43, strlen($token));
        $this->assertStringNotContainsString('=', $token);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\-_]+$/', $token);
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
            $this->eventBus,
            $this->uuidFactory,
            $this->ulidFactory,
        );
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
