<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SignInCommand;
use App\User\Application\CommandHandler\SignInCommandHandler;
use App\User\Application\Service\IssuedSession;
use App\User\Application\Service\SessionIssuanceServiceInterface;
use App\User\Application\Service\SignInEventPublisherInterface;
use App\User\Application\Service\UserAuthenticationServiceInterface;
use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Ulid;

final class SignInCommandHandlerTest extends UnitTestCase
{
    private UserAuthenticationServiceInterface&MockObject $authService;
    private SessionIssuanceServiceInterface&MockObject $sessionIssuanceService;
    private SignInEventPublisherInterface&MockObject $eventPublisher;
    private PendingTwoFactorRepositoryInterface&MockObject $pendingTwoFactorRepository;
    private UlidFactory&MockObject $ulidFactory;

    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->authService = $this->createMock(UserAuthenticationServiceInterface::class);
        $this->sessionIssuanceService = $this->createMock(SessionIssuanceServiceInterface::class);
        $this->eventPublisher = $this->createMock(SignInEventPublisherInterface::class);
        $this->pendingTwoFactorRepository = $this->createMock(PendingTwoFactorRepositoryInterface::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);

        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
    }

    public function testInvokeReturnsTokensForUserWithoutTwoFactor(): void
    {
        $email = $this->faker->email();
        $plainPassword = $this->faker->password();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $user = $this->createUser($email);
        $sessionId = 'test-session-id';
        $accessToken = 'signed-access-token';
        $refreshToken = str_repeat('a', 43);

        $this->authService
            ->expects($this->once())
            ->method('authenticate')
            ->with($email, $plainPassword, $ipAddress, $userAgent)
            ->willReturn($user);

        $this->sessionIssuanceService
            ->expects($this->once())
            ->method('issue')
            ->with($user, $ipAddress, $userAgent, false, $this->isInstanceOf(DateTimeImmutable::class))
            ->willReturn(new IssuedSession($sessionId, $accessToken, $refreshToken));

        $this->eventPublisher
            ->expects($this->once())
            ->method('publishSignedIn')
            ->with($user->getId(), $user->getEmail(), $sessionId, $ipAddress, $userAgent, false);

        $this->pendingTwoFactorRepository
            ->expects($this->never())
            ->method('save');

        $handler = $this->createHandler();
        $command = new SignInCommand($email, $plainPassword, false, $ipAddress, $userAgent);

        $handler->__invoke($command);

        $this->assertFalse($command->getResponse()->isTwoFactorEnabled());
        $this->assertSame($accessToken, $command->getResponse()->getAccessToken());
        $this->assertSame($refreshToken, $command->getResponse()->getRefreshToken());
        $this->assertSame(43, strlen((string) $command->getResponse()->getRefreshToken()));
    }

    public function testInvokeThrowsUnauthorizedWhenPasswordIsInvalid(): void
    {
        $email = $this->faker->email();
        $plainPassword = $this->faker->password();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();

        $this->authService
            ->expects($this->once())
            ->method('authenticate')
            ->with($email, $plainPassword, $ipAddress, $userAgent)
            ->willThrowException(
                new UnauthorizedHttpException('Bearer', 'Invalid credentials.')
            );

        $this->sessionIssuanceService
            ->expects($this->never())
            ->method('issue');

        $handler = $this->createHandler();
        $command = new SignInCommand($email, $plainPassword, false, $ipAddress, $userAgent);

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
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $user = $this->createUser($email);

        $this->authService
            ->expects($this->once())
            ->method('authenticate')
            ->willReturn($user);

        $this->sessionIssuanceService
            ->expects($this->once())
            ->method('issue')
            ->with($user, $ipAddress, $userAgent, true, $this->isInstanceOf(DateTimeImmutable::class))
            ->willReturn(new IssuedSession('session-id', 'remember-token', str_repeat('b', 43)));

        $this->eventPublisher
            ->expects($this->once())
            ->method('publishSignedIn');

        $handler = $this->createHandler();
        $command = new SignInCommand($email, $plainPassword, true, $ipAddress, $userAgent);

        $handler->__invoke($command);

        $this->assertSame('remember-token', $command->getResponse()->getAccessToken());
    }

    public function testInvokeReturnsTwoFactorResponseWhenTwoFactorIsEnabled(): void
    {
        $email = $this->faker->email();
        $plainPassword = $this->faker->password();
        $user = $this->createUser($email);
        $user->setTwoFactorEnabled(true);
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();

        $this->authService
            ->expects($this->once())
            ->method('authenticate')
            ->willReturn($user);

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

        $this->sessionIssuanceService
            ->expects($this->never())
            ->method('issue');

        $this->eventPublisher
            ->expects($this->never())
            ->method('publishSignedIn');

        $handler = $this->createHandler();
        $command = new SignInCommand($email, $plainPassword, false, $ipAddress, $userAgent);

        $handler->__invoke($command);

        $this->assertTrue($command->getResponse()->isTwoFactorEnabled());
        $this->assertSame((string) $pendingSessionId, $command->getResponse()->getPendingSessionId());
        $this->assertNull($command->getResponse()->getAccessToken());
        $this->assertNull($command->getResponse()->getRefreshToken());
    }

    public function testInvokeStoresRememberMeInPendingTwoFactorWhenTwoFactorIsEnabled(): void
    {
        $email = $this->faker->email();
        $user = $this->createUser($email);
        $user->setTwoFactorEnabled(true);

        $this->authService
            ->method('authenticate')
            ->willReturn($user);

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
            $this->faker->password(),
            true,
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );

        $handler->__invoke($command);

        $this->assertTrue($command->getResponse()->isTwoFactorEnabled());
    }

    public function testInvokeThrowsLockedWhenFailureThresholdReached(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('authenticate')
            ->willThrowException(
                new LockedHttpException(
                    'Account temporarily locked',
                    null,
                    0,
                    ['Retry-After' => '900']
                )
            );

        $handler = $this->createHandler();
        $command = new SignInCommand(
            $this->faker->email(),
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
            $this->assertSame('900', $exception->getHeaders()['Retry-After'] ?? null);
        }
    }

    public function testInvokeThrowsLockedWhenAccountAlreadyLocked(): void
    {
        $this->authService
            ->expects($this->once())
            ->method('authenticate')
            ->willThrowException(
                new LockedHttpException(
                    'Account temporarily locked',
                    null,
                    0,
                    ['Retry-After' => '900']
                )
            );

        $handler = $this->createHandler();
        $command = new SignInCommand(
            $this->faker->email(),
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

    public function testDefaultTtlIsThreeHundredSeconds(): void
    {
        $email = $this->faker->email();
        $user = $this->createUser($email);
        $user->setTwoFactorEnabled(true);

        $this->authService->method('authenticate')->willReturn($user);

        $pendingSessionId = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FB5');
        $this->ulidFactory->method('create')->willReturn($pendingSessionId);

        $this->pendingTwoFactorRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (PendingTwoFactor $pf): bool => $pf->getExpiresAt()->getTimestamp() - $pf->getCreatedAt()->getTimestamp() === 300
            ));

        $handler = new SignInCommandHandler(
            $this->authService,
            $this->sessionIssuanceService,
            $this->eventPublisher,
            $this->pendingTwoFactorRepository,
            $this->ulidFactory,
        );

        $command = new SignInCommand(
            $email,
            $this->faker->password(),
            false,
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );

        $handler->__invoke($command);
    }

    private function createHandler(): SignInCommandHandler
    {
        return new SignInCommandHandler(
            $this->authService,
            $this->sessionIssuanceService,
            $this->eventPublisher,
            $this->pendingTwoFactorRepository,
            $this->ulidFactory,
            300,
        );
    }

    private function createUser(string $email): User
    {
        return $this->userFactory->create(
            $email,
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
    }
}
