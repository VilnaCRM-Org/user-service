<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Authenticator;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Processor\Authenticator\UserAuthenticator;
use App\User\Application\Processor\EventPublisher\SignInEventsInterface;
use App\User\Application\Processor\Hasher\PasswordHasherInterface;
use App\User\Application\Processor\Lockout\AccountLockoutServiceInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class UserAuthenticatorTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PasswordHasherInterface&MockObject $passwordHasher;
    private AccountLockoutServiceInterface&MockObject $lockoutService;
    private SignInEventsInterface&MockObject $events;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->lockoutService = $this->createMock(AccountLockoutServiceInterface::class);
        $this->events = $this->createMock(SignInEventsInterface::class);
    }

    public function testAuthenticateSuccessWithoutRehash(): void
    {
        $email = $this->faker->email();
        $password = $this->faker->password();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $hashedPassword = $this->faker->sha256();
        $dummyHash = $this->faker->sha256();

        $user = $this->createUserMock($hashedPassword);

        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->passwordHasher->method('verify')->willReturn(true);
        $this->passwordHasher->method('needsRehash')->willReturn(false);

        $this->lockoutService->expects($this->once())
            ->method('clearFailures');

        $this->userRepository->expects($this->never())
            ->method('save');

        $authenticator = $this->createAuthenticator($dummyHash);
        $result = $authenticator->authenticate($email, $password, $ipAddress, $userAgent);

        $this->assertSame($user, $result);
    }

    public function testAuthenticateSuccessWithRehash(): void
    {
        $password = $this->faker->password();
        $newHash = $this->faker->sha256();
        $dummyHash = $this->faker->sha256();

        $user = $this->arrangeSuccessfulAuth(true);
        $this->passwordHasher->method('hash')
            ->with($password)->willReturn($newHash);
        $user->expects($this->once())
            ->method('upgradePasswordHash')->with($newHash);
        $this->userRepository->expects($this->once())
            ->method('save')->with($user);

        $auth = $this->createAuthenticator($dummyHash);
        $result = $auth->authenticate(
            $this->faker->email(),
            $password,
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
        $this->assertSame($user, $result);
    }

    public function testAuthenticateNormalizesEmail(): void
    {
        $rawEmail = '  Test@Example.COM  ';
        $normalizedEmail = 'test@example.com';
        $password = $this->faker->password();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $hashedPassword = $this->faker->sha256();
        $dummyHash = $this->faker->sha256();

        $user = $this->createUserMock($hashedPassword);

        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($normalizedEmail)
            ->willReturn($user);
        $this->passwordHasher->method('verify')->willReturn(true);
        $this->passwordHasher->method('needsRehash')->willReturn(false);

        $authenticator = $this->createAuthenticator($dummyHash);
        $authenticator->authenticate($rawEmail, $password, $ipAddress, $userAgent);
    }

    public function testAuthenticateThrowsLockedWhenAccountIsLocked(): void
    {
        $email = $this->faker->email();
        $maxAttempts = $this->faker->numberBetween(3, 20);
        $lockoutSeconds = $this->faker->numberBetween(60, 3600);

        $this->lockoutService->method('isLocked')->willReturn(true);
        $this->lockoutService->method('maxAttempts')->willReturn($maxAttempts);
        $this->lockoutService->method('lockoutSeconds')->willReturn($lockoutSeconds);

        $this->events->expects($this->once())
            ->method('publishLockedOut')
            ->with(strtolower(trim($email)), $maxAttempts, $lockoutSeconds);

        $authenticator = $this->createAuthenticator($this->faker->sha256());

        $this->expectException(LockedHttpException::class);
        $this->expectExceptionMessage('Account temporarily locked');

        $authenticator->authenticate(
            $email,
            $this->faker->password(),
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
    }

    public function testLockedExceptionHasRetryAfterHeader(): void
    {
        $lockoutSeconds = $this->faker->numberBetween(60, 3600);
        $this->arrangeLocked($lockoutSeconds);

        $exception = $this->catchLockedException();

        $this->assertLockedExceptionHeaders($exception, $lockoutSeconds);
    }

    public function testLockedExceptionAfterFailureHasRetryAfterHeader(): void
    {
        $lockoutSeconds = $this->faker->numberBetween(60, 3600);
        $this->arrangeFailedAuth();
        $this->lockoutService->method('recordFailure')->willReturn(true);
        $this->arrangeLockoutDetails($lockoutSeconds);

        $exception = $this->catchLockedException();

        $this->assertLockedExceptionHeaders($exception, $lockoutSeconds);
    }

    public function testUnauthorizedExceptionHasCorrectStatusCode(): void
    {
        $this->arrangeFailedAuth();
        $this->lockoutService->method('recordFailure')->willReturn(false);

        $authenticator = $this->createAuthenticator($this->faker->sha256());

        try {
            $authenticator->authenticate(
                $this->faker->email(),
                $this->faker->password(),
                $this->faker->ipv4(),
                $this->faker->userAgent()
            );
            $this->fail('Expected UnauthorizedHttpException was not thrown');
        } catch (UnauthorizedHttpException $exception) {
            $this->assertSame(401, $exception->getStatusCode());
        }
    }

    public function testDummyPasswordVerifiedWhenUserNotFound(): void
    {
        $dummyHash = $this->faker->sha256();
        $password = $this->faker->password();

        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->userRepository->method('findByEmail')->willReturn(null);
        $this->lockoutService->method('recordFailure')->willReturn(false);

        $this->passwordHasher->expects($this->once())
            ->method('verify')
            ->with($dummyHash, $password)
            ->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);

        $auth = $this->createAuthenticator($dummyHash);
        $auth->authenticate(
            $this->faker->email(),
            $password,
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
    }

    public function testAuthenticateThrowsUnauthorizedWhenUserNotFound(): void
    {
        $this->arrangeFailedAuth();
        $this->lockoutService->method('recordFailure')->willReturn(false);
        $this->events->expects($this->once())->method('publishFailed');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid credentials.');

        $auth = $this->createAuthenticator($this->faker->sha256());
        $auth->authenticate(
            $this->faker->email(),
            $this->faker->password(),
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
    }

    public function testAuthenticateThrowsUnauthorizedWhenPasswordWrong(): void
    {
        $user = $this->createUserMock($this->faker->sha256());
        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->passwordHasher->method('verify')->willReturn(false);
        $this->lockoutService->method('recordFailure')->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid credentials.');

        $auth = $this->createAuthenticator($this->faker->sha256());
        $auth->authenticate(
            $this->faker->email(),
            $this->faker->password(),
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
    }

    public function testAuthenticateThrowsLockedWhenFailureCausesLockout(): void
    {
        $email = $this->faker->email();
        $this->arrangeFailedAuth();
        $this->lockoutService->method('recordFailure')->willReturn(true);
        $max = $this->faker->numberBetween(3, 20);
        $secs = $this->faker->numberBetween(60, 3600);
        $this->lockoutService->method('maxAttempts')->willReturn($max);
        $this->lockoutService->method('lockoutSeconds')->willReturn($secs);

        $this->events->expects($this->once())->method('publishFailed');
        $this->events->expects($this->once())
            ->method('publishLockedOut')
            ->with(strtolower(trim($email)), $max, $secs);

        $this->expectException(LockedHttpException::class);
        $auth = $this->createAuthenticator($this->faker->sha256());
        $auth->authenticate(
            $email,
            $this->faker->password(),
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
    }

    public function testConstructorHashesDummyPasswordWhenNoHashProvided(): void
    {
        $expectedHash = $this->faker->sha256();
        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->willReturn($expectedHash);

        new UserAuthenticator(
            $this->userRepository,
            $this->passwordHasher,
            $this->lockoutService,
            $this->events,
            null
        );
    }

    public function testConstructorHashesDummyPasswordWhenEmptyStringProvided(): void
    {
        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->willReturn($this->faker->sha256());

        new UserAuthenticator(
            $this->userRepository,
            $this->passwordHasher,
            $this->lockoutService,
            $this->events,
            ''
        );
    }

    public function testConstructorUsesDummyHashWhenProvided(): void
    {
        $dummyHash = $this->faker->sha256();

        $this->passwordHasher->expects($this->never())
            ->method('hash');

        new UserAuthenticator(
            $this->userRepository,
            $this->passwordHasher,
            $this->lockoutService,
            $this->events,
            $dummyHash
        );
    }

    private function arrangeSuccessfulAuth(bool $needsRehash): User&MockObject
    {
        $user = $this->createUserMock($this->faker->sha256());
        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->passwordHasher->method('verify')->willReturn(true);
        $this->passwordHasher->method('needsRehash')
            ->willReturn($needsRehash);

        return $user;
    }

    private function arrangeFailedAuth(): void
    {
        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->userRepository->method('findByEmail')->willReturn(null);
        $this->passwordHasher->method('verify')->willReturn(false);
    }

    private function createUserMock(string $hashedPassword): User&MockObject
    {
        $user = $this->createMock(User::class);
        $user->method('getPassword')->willReturn($hashedPassword);

        return $user;
    }

    private function createAuthenticator(string $dummyHash): UserAuthenticator
    {
        return new UserAuthenticator(
            $this->userRepository,
            $this->passwordHasher,
            $this->lockoutService,
            $this->events,
            $dummyHash
        );
    }

    private function arrangeLocked(int $lockoutSeconds): void
    {
        $this->lockoutService->method('isLocked')->willReturn(true);
        $this->arrangeLockoutDetails($lockoutSeconds);
    }

    private function arrangeLockoutDetails(int $lockoutSeconds): void
    {
        $this->lockoutService->method('maxAttempts')->willReturn(5);
        $this->lockoutService->method('lockoutSeconds')
            ->willReturn($lockoutSeconds);
    }

    private function catchLockedException(): LockedHttpException
    {
        $authenticator = $this->createAuthenticator($this->faker->sha256());

        try {
            $authenticator->authenticate(
                $this->faker->email(),
                $this->faker->password(),
                $this->faker->ipv4(),
                $this->faker->userAgent()
            );
            $this->fail('Expected LockedHttpException was not thrown');
        } catch (LockedHttpException $exception) {
            return $exception;
        }
    }

    private function assertLockedExceptionHeaders(
        LockedHttpException $exception,
        int $lockoutSeconds
    ): void {
        $this->assertSame(423, $exception->getStatusCode());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame(
            (string) $lockoutSeconds,
            $exception->getHeaders()['Retry-After']
        );
    }
}
