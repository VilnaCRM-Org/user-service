<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Application\CommandHandler\Fixture\RecordingPendingTwoFactorRepository;
use App\User\Application\Command\SignInCommand;
use App\User\Application\CommandHandler\SignInCommandHandler;
use App\User\Application\Factory\AuthTokenFactoryInterface;
use App\User\Domain\Contract\AccessTokenGeneratorInterface;
use App\User\Domain\Contract\AccountLockoutServiceInterface;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\PendingTwoFactorFactory;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Ulid;

abstract class SignInCommandHandlerTestCase extends UnitTestCase
{
    protected UserRepositoryInterface&MockObject $userRepository;
    protected PasswordHasherInterface&MockObject $passwordHasher;
    protected AccountLockoutServiceInterface&MockObject $lockoutService;
    protected AuthSessionRepositoryInterface&MockObject $authSessionRepository;
    protected AuthRefreshTokenRepositoryInterface&MockObject $authRefreshTokenRepository;
    protected AccessTokenGeneratorInterface&MockObject $accessTokenGenerator;
    protected AuthTokenFactoryInterface&MockObject $authTokenFactory;
    protected EventBusInterface&MockObject $eventBus;
    protected RecordingPendingTwoFactorRepository $pendingTwoFactorRepository;
    protected PendingTwoFactorFactory $pendingTwoFactorFactory;
    protected UlidFactory&MockObject $ulidFactory;
    protected UserFactory $userFactory;
    protected UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->lockoutService = $this->createMock(AccountLockoutServiceInterface::class);
        $this->authSessionRepository = $this->createMock(AuthSessionRepositoryInterface::class);
        $this->authRefreshTokenRepository = $this->createMock(AuthRefreshTokenRepositoryInterface::class);
        $this->accessTokenGenerator = $this->createMock(AccessTokenGeneratorInterface::class);
        $this->authTokenFactory = $this->createMock(AuthTokenFactoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->pendingTwoFactorRepository = new RecordingPendingTwoFactorRepository();
        $this->pendingTwoFactorFactory = new PendingTwoFactorFactory();
        $this->ulidFactory = $this->createMock(UlidFactory::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());

        $this->configureDefaultAuthTokenFactory();
        $this->configureDefaultRefreshTokenRepository();
    }

    protected function configureDefaultAuthTokenFactory(): void
    {
        $this->authTokenFactory->method('generateOpaqueToken')
            ->willReturn('test-opaque-token-1234567890-abcdefghijklmn');
        $this->authTokenFactory->method('createRefreshToken')
            ->willReturnCallback(
                static function (
                    string $sessionId,
                    string $plain,
                    DateTimeImmutable $issuedAt
                ): AuthRefreshToken {
                    return new AuthRefreshToken(
                        (string) new Ulid(),
                        $sessionId,
                        $plain,
                        $issuedAt->modify('+1 month')
                    );
                }
            );
        $this->authTokenFactory->method('buildJwtPayload')
            ->willReturn([
                'sub' => 'test-user-id',
                'iss' => 'vilnacrm-user-service',
                'aud' => 'vilnacrm-api',
                'exp' => time() + 900,
                'iat' => time(),
                'nbf' => time(),
                'jti' => 'test-jti',
                'sid' => 'test-session-id',
                'roles' => ['ROLE_USER'],
            ]);
        $this->authTokenFactory->method('nextEventId')
            ->willReturn('test-event-id');
    }

    protected function configureDefaultRefreshTokenRepository(): void
    {
        $this->accessTokenGenerator->method('generate')
            ->willReturn('issued-access-token');
    }

    protected function createHandler(): SignInCommandHandler
    {
        return new SignInCommandHandler(
            $this->userRepository,
            $this->passwordHasher,
            $this->lockoutService,
            $this->authSessionRepository,
            $this->authRefreshTokenRepository,
            $this->accessTokenGenerator,
            $this->authTokenFactory,
            $this->eventBus,
            $this->pendingTwoFactorRepository,
            $this->pendingTwoFactorFactory,
            $this->ulidFactory,
            '$2y$04$test.dummy.hash.that.is.valid.bcrypt.placeholder',
        );
    }

    /**
     * @return array{User, string, string, string, string}
     */
    protected function arrangeCredentials(): array
    {
        $email = strtolower($this->faker->email());
        $plainPassword = $this->faker->password();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();

        return [
            $this->createUser($email),
            $email,
            $plainPassword,
            $ipAddress,
            $userAgent,
        ];
    }

    protected function createRandomSignInCommand(): SignInCommand
    {
        return new SignInCommand(
            $this->faker->email(),
            $this->faker->password(),
            false,
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
    }

    protected function createUser(string $email): User
    {
        return $this->userFactory->create(
            $email,
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
    }
}
