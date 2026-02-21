<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Service;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Service\SignInEventPublisherInterface;
use App\User\Application\Service\UserAuthenticationService;
use App\User\Domain\Contract\AccountLockoutServiceInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class UserAuthenticationServiceTest extends UnitTestCase
{
    private UserRepositoryInterface $userRepository;
    private PasswordHasherFactoryInterface $hasherFactory;
    private PasswordHasherInterface $hasher;
    private AccountLockoutServiceInterface $lockoutService;
    private SignInEventPublisherInterface $eventPublisher;
    private UuidTransformer $uuidTransformer;
    private UserFactory $userFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->hasherFactory = $this->createMock(PasswordHasherFactoryInterface::class);
        $this->hasher = $this->createMock(PasswordHasherInterface::class);
        $this->lockoutService = $this->createMock(AccountLockoutServiceInterface::class);
        $this->eventPublisher = $this->createMock(SignInEventPublisherInterface::class);
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());
        $this->userFactory = new UserFactory();
    }

    public function testAuthenticateSucceeds(): void
    {
        $email = $this->faker->email();
        $password = $this->faker->password();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $hashedPassword = $this->faker->sha256();

        $user = $this->createUser($email, $hashedPassword);

        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->userRepository->method('findByEmail')->with($email)->willReturn($user);
        $this->hasherFactory->method('getPasswordHasher')->with(User::class)->willReturn($this->hasher);
        $this->hasher->method('verify')->with($hashedPassword, $password)->willReturn(true);
        $this->lockoutService->expects($this->once())->method('clearFailures')->with($email);

        $result = $this->createService()->authenticate($email, $password, $ipAddress, $userAgent);

        $this->assertSame($user, $result);
    }

    public function testAuthenticateFailsWhenUserNotFound(): void
    {
        $email = $this->faker->email();
        $password = $this->faker->password();
        $dummyHash = $this->faker->sha256();

        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->userRepository->method('findByEmail')->willReturn(null);
        $this->hasherFactory->method('getPasswordHasher')->with(User::class)->willReturn($this->hasher);
        $this->hasher->method('verify')->willReturn(false);
        $this->lockoutService->method('recordFailure')->willReturn(false);
        $this->eventPublisher->expects($this->once())->method('publishFailed')
            ->with($email, $this->anything(), $this->anything(), 'invalid_credentials');

        $this->expectException(UnauthorizedHttpException::class);
        $this->createService($dummyHash)->authenticate($email, $password, $this->faker->ipv4(), $this->faker->userAgent());
    }

    public function testAuthenticateFailsOnWrongPassword(): void
    {
        $email = $this->faker->email();
        $password = $this->faker->password();

        $user = $this->createUser($email, $this->faker->sha256());

        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->hasherFactory->method('getPasswordHasher')->willReturn($this->hasher);
        $this->hasher->method('verify')->willReturn(false);
        $this->lockoutService->method('recordFailure')->willReturn(false);
        $this->eventPublisher->expects($this->once())->method('publishFailed');

        $this->expectException(UnauthorizedHttpException::class);
        $this->createService()->authenticate($email, $password, $this->faker->ipv4(), $this->faker->userAgent());
    }

    public function testAuthenticateThrowsLockedWhenEmailIsAlreadyLocked(): void
    {
        $email = $this->faker->email();

        $this->lockoutService->method('isLocked')->willReturn(true);
        $this->eventPublisher->expects($this->once())->method('publishLockedOut')
            ->with($email, AccountLockoutServiceInterface::MAX_ATTEMPTS, AccountLockoutServiceInterface::LOCKOUT_SECONDS);

        $this->expectException(LockedHttpException::class);
        $this->createService()->authenticate($email, $this->faker->password(), $this->faker->ipv4(), $this->faker->userAgent());
    }

    public function testAuthenticateThrowsLockedWhenFailureBecomesLocked(): void
    {
        $email = $this->faker->email();

        $user = $this->createUser($email, $this->faker->sha256());

        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->hasherFactory->method('getPasswordHasher')->willReturn($this->hasher);
        $this->hasher->method('verify')->willReturn(false);
        $this->lockoutService->method('recordFailure')->willReturn(true);
        $this->eventPublisher->expects($this->once())->method('publishFailed');
        $this->eventPublisher->expects($this->once())->method('publishLockedOut')
            ->with($email, AccountLockoutServiceInterface::MAX_ATTEMPTS, AccountLockoutServiceInterface::LOCKOUT_SECONDS);

        $this->expectException(LockedHttpException::class);
        $this->createService()->authenticate($email, $this->faker->password(), $this->faker->ipv4(), $this->faker->userAgent());
    }

    public function testAuthenticateThrowsUnauthorizedWhenFailureDoesNotLock(): void
    {
        $email = $this->faker->email();

        $user = $this->createUser($email, $this->faker->sha256());

        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->hasherFactory->method('getPasswordHasher')->willReturn($this->hasher);
        $this->hasher->method('verify')->willReturn(false);
        $this->lockoutService->method('recordFailure')->willReturn(false);
        $this->eventPublisher->expects($this->once())->method('publishFailed');
        $this->eventPublisher->expects($this->never())->method('publishLockedOut');

        $this->expectException(UnauthorizedHttpException::class);
        $this->createService()->authenticate($email, $this->faker->password(), $this->faker->ipv4(), $this->faker->userAgent());
    }

    public function testNormalizeEmailTrimsAndLowercases(): void
    {
        $rawEmail = '  ' . strtoupper($this->faker->email()) . '  ';
        $normalizedEmail = strtolower(trim($rawEmail));

        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($normalizedEmail)
            ->willReturn(null);
        $this->hasherFactory->method('getPasswordHasher')->willReturn($this->hasher);
        $this->hasher->method('verify')->willReturn(false);
        $this->lockoutService->method('recordFailure')->willReturn(false);
        $this->eventPublisher->method('publishFailed');

        $this->expectException(UnauthorizedHttpException::class);
        $this->createService()->authenticate($rawEmail, $this->faker->password(), $this->faker->ipv4(), $this->faker->userAgent());
    }

    public function testResolveDummyHashUsesProvidedHash(): void
    {
        $dummyHash = $this->faker->sha256();
        $password = $this->faker->password();
        $email = $this->faker->email();

        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->userRepository->method('findByEmail')->willReturn(null);
        $this->hasherFactory->method('getPasswordHasher')->willReturn($this->hasher);
        $this->hasher->expects($this->once())->method('verify')->with($dummyHash, $password);
        $this->lockoutService->method('recordFailure')->willReturn(false);
        $this->eventPublisher->method('publishFailed');

        $this->expectException(UnauthorizedHttpException::class);
        $this->createService($dummyHash)->authenticate($email, $password, $this->faker->ipv4(), $this->faker->userAgent());
    }

    public function testResolveDummyHashGeneratesHashWhenNotProvided(): void
    {
        $generatedHash = $this->faker->sha256();

        $this->hasherFactory->method('getPasswordHasher')->willReturn($this->hasher);
        $this->hasher->method('hash')->willReturn($generatedHash);

        $service = new UserAuthenticationService(
            $this->userRepository,
            $this->hasherFactory,
            $this->lockoutService,
            $this->eventPublisher,
            null
        );

        $email = $this->faker->email();
        $password = $this->faker->password();

        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->userRepository->method('findByEmail')->willReturn(null);
        $this->hasher->expects($this->once())->method('verify')->with($generatedHash, $password);
        $this->lockoutService->method('recordFailure')->willReturn(false);
        $this->eventPublisher->method('publishFailed');

        $this->expectException(UnauthorizedHttpException::class);
        $service->authenticate($email, $password, $this->faker->ipv4(), $this->faker->userAgent());
    }

    public function testLockedExceptionCarriesRetryAfterHeader(): void
    {
        $email = $this->faker->email();

        $this->lockoutService->method('isLocked')->willReturn(true);
        $this->eventPublisher->method('publishLockedOut');

        try {
            $this->createService()->authenticate($email, $this->faker->password(), $this->faker->ipv4(), $this->faker->userAgent());
            $this->fail('Expected LockedHttpException');
        } catch (LockedHttpException $exception) {
            $this->assertSame(
                (string) AccountLockoutServiceInterface::LOCKOUT_SECONDS,
                $exception->getHeaders()['Retry-After']
            );
        }
    }

    private function createUser(string $email, string $hashedPassword): User
    {
        return $this->userFactory->create(
            $email,
            $this->faker->name(),
            $hashedPassword,
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
    }

    private function createService(string $dummyHash = 'test-dummy-hash'): UserAuthenticationService
    {
        return new UserAuthenticationService(
            $this->userRepository,
            $this->hasherFactory,
            $this->lockoutService,
            $this->eventPublisher,
            $dummyHash
        );
    }
}
