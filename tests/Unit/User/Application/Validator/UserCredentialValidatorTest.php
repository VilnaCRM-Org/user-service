<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Validator;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Provider\AccountLockoutProviderInterface;
use App\User\Application\Validator\UserCredentialValidator;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Publisher\SignInPublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class UserCredentialValidatorTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PasswordHasherInterface&MockObject $passwordHasher;
    private AccountLockoutProviderInterface&MockObject $lockoutGuard;
    private SignInPublisherInterface&MockObject $events;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->lockoutGuard = $this->createMock(AccountLockoutProviderInterface::class);
        $this->events = $this->createMock(SignInPublisherInterface::class);
    }

    public function testValidateSuccessWithoutRehash(): void
    {
        $email = $this->faker->email();
        $password = $this->faker->password();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $hashedPassword = $this->faker->sha256();
        $dummyHash = $this->faker->sha256();

        $user = $this->createUserMock($hashedPassword);

        $this->lockoutGuard->method('isLocked')->willReturn(false);
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->passwordHasher->method('verify')->willReturn(true);
        $this->passwordHasher->method('needsRehash')->willReturn(false);

        $this->lockoutGuard->expects($this->once())
            ->method('clearFailures');

        $this->userRepository->expects($this->never())
            ->method('save');

        $validator = $this->createValidator($dummyHash);
        $result = $validator->validate($email, $password, $ipAddress, $userAgent);

        $this->assertSame($user, $result);
    }

    public function testValidateSuccessWithRehash(): void
    {
        $password = $this->faker->password();
        $newHash = $this->faker->sha256();
        $dummyHash = $this->faker->sha256();

        $user = $this->arrangeSuccessfulValidation(true);
        $this->passwordHasher->method('hash')
            ->with($password)->willReturn($newHash);
        $user->expects($this->once())
            ->method('upgradePasswordHash')->with($newHash);
        $this->userRepository->expects($this->once())
            ->method('save')->with($user);

        $validator = $this->createValidator($dummyHash);
        $result = $validator->validate(
            $this->faker->email(),
            $password,
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
        $this->assertSame($user, $result);
    }

    public function testValidateNormalizesEmail(): void
    {
        $rawEmail = '  Test@Example.COM  ';
        $normalizedEmail = 'test@example.com';
        $password = $this->faker->password();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $hashedPassword = $this->faker->sha256();
        $dummyHash = $this->faker->sha256();

        $user = $this->createUserMock($hashedPassword);

        $this->lockoutGuard->method('isLocked')->willReturn(false);
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($normalizedEmail)
            ->willReturn($user);
        $this->passwordHasher->method('verify')->willReturn(true);
        $this->passwordHasher->method('needsRehash')->willReturn(false);

        $validator = $this->createValidator($dummyHash);
        $validator->validate($rawEmail, $password, $ipAddress, $userAgent);
    }

    public function testValidateThrowsLockedWhenAccountIsLocked(): void
    {
        $email = $this->faker->email();
        $maxAttempts = $this->faker->numberBetween(3, 20);
        $lockoutSeconds = $this->faker->numberBetween(60, 3600);

        $this->lockoutGuard->method('isLocked')->willReturn(true);
        $this->lockoutGuard->method('maxAttempts')->willReturn($maxAttempts);
        $this->lockoutGuard->method('lockoutSeconds')->willReturn($lockoutSeconds);

        $this->events->expects($this->once())
            ->method('publishLockedOut')
            ->with(strtolower(trim($email)), $maxAttempts, $lockoutSeconds);

        $validator = $this->createValidator($this->faker->sha256());

        $this->expectException(LockedHttpException::class);
        $this->expectExceptionMessage('Account temporarily locked');

        $validator->validate(
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
        $this->arrangeFailedValidation();
        $this->lockoutGuard->method('recordFailure')->willReturn(true);
        $this->arrangeLockoutDetails($lockoutSeconds);

        $exception = $this->catchLockedException();

        $this->assertLockedExceptionHeaders($exception, $lockoutSeconds);
    }

    public function testUnauthorizedExceptionHasCorrectStatusCode(): void
    {
        $this->arrangeFailedValidation();
        $this->lockoutGuard->method('recordFailure')->willReturn(false);

        $validator = $this->createValidator($this->faker->sha256());

        try {
            $validator->validate(
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

        $this->lockoutGuard->method('isLocked')->willReturn(false);
        $this->userRepository->method('findByEmail')->willReturn(null);
        $this->lockoutGuard->method('recordFailure')->willReturn(false);

        $this->passwordHasher->expects($this->once())
            ->method('verify')
            ->with($dummyHash, $password)
            ->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);

        $validator = $this->createValidator($dummyHash);
        $validator->validate(
            $this->faker->email(),
            $password,
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
    }

    public function testValidateThrowsUnauthorizedWhenUserNotFound(): void
    {
        $this->arrangeFailedValidation();
        $this->lockoutGuard->method('recordFailure')->willReturn(false);
        $this->events->expects($this->once())->method('publishFailed');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid credentials.');

        $validator = $this->createValidator($this->faker->sha256());
        $validator->validate(
            $this->faker->email(),
            $this->faker->password(),
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
    }

    public function testValidateThrowsUnauthorizedWhenPasswordWrong(): void
    {
        $user = $this->createUserMock($this->faker->sha256());
        $this->lockoutGuard->method('isLocked')->willReturn(false);
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->passwordHasher->method('verify')->willReturn(false);
        $this->lockoutGuard->method('recordFailure')->willReturn(false);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid credentials.');

        $validator = $this->createValidator($this->faker->sha256());
        $validator->validate(
            $this->faker->email(),
            $this->faker->password(),
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
    }

    public function testValidateThrowsLockedWhenFailureCausesLockout(): void
    {
        $email = $this->faker->email();
        $this->arrangeFailedValidation();
        $this->lockoutGuard->method('recordFailure')->willReturn(true);
        $max = $this->faker->numberBetween(3, 20);
        $secs = $this->faker->numberBetween(60, 3600);
        $this->lockoutGuard->method('maxAttempts')->willReturn($max);
        $this->lockoutGuard->method('lockoutSeconds')->willReturn($secs);

        $this->events->expects($this->once())->method('publishFailed');
        $this->events->expects($this->once())
            ->method('publishLockedOut')
            ->with(strtolower(trim($email)), $max, $secs);

        $this->expectException(LockedHttpException::class);
        $validator = $this->createValidator($this->faker->sha256());
        $validator->validate(
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

        new UserCredentialValidator(
            $this->userRepository,
            $this->passwordHasher,
            $this->lockoutGuard,
            $this->events,
            null
        );
    }

    public function testConstructorHashesDummyPasswordWhenEmptyStringProvided(): void
    {
        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->willReturn($this->faker->sha256());

        new UserCredentialValidator(
            $this->userRepository,
            $this->passwordHasher,
            $this->lockoutGuard,
            $this->events,
            ''
        );
    }

    public function testConstructorUsesDummyHashWhenProvided(): void
    {
        $dummyHash = $this->faker->sha256();

        $this->passwordHasher->expects($this->never())
            ->method('hash');

        new UserCredentialValidator(
            $this->userRepository,
            $this->passwordHasher,
            $this->lockoutGuard,
            $this->events,
            $dummyHash
        );
    }

    private function arrangeSuccessfulValidation(bool $needsRehash): User&MockObject
    {
        $user = $this->createUserMock($this->faker->sha256());
        $this->lockoutGuard->method('isLocked')->willReturn(false);
        $this->userRepository->method('findByEmail')->willReturn($user);
        $this->passwordHasher->method('verify')->willReturn(true);
        $this->passwordHasher->method('needsRehash')
            ->willReturn($needsRehash);

        return $user;
    }

    private function arrangeFailedValidation(): void
    {
        $this->lockoutGuard->method('isLocked')->willReturn(false);
        $this->userRepository->method('findByEmail')->willReturn(null);
        $this->passwordHasher->method('verify')->willReturn(false);
    }

    private function createUserMock(string $hashedPassword): User&MockObject
    {
        $user = $this->createMock(User::class);
        $user->method('getPassword')->willReturn($hashedPassword);

        return $user;
    }

    private function createValidator(string $dummyHash): UserCredentialValidator
    {
        return new UserCredentialValidator(
            $this->userRepository,
            $this->passwordHasher,
            $this->lockoutGuard,
            $this->events,
            $dummyHash
        );
    }

    private function arrangeLocked(int $lockoutSeconds): void
    {
        $this->lockoutGuard->method('isLocked')->willReturn(true);
        $this->arrangeLockoutDetails($lockoutSeconds);
    }

    private function arrangeLockoutDetails(int $lockoutSeconds): void
    {
        $this->lockoutGuard->method('maxAttempts')->willReturn(5);
        $this->lockoutGuard->method('lockoutSeconds')
            ->willReturn($lockoutSeconds);
    }

    private function catchLockedException(): LockedHttpException
    {
        $validator = $this->createValidator($this->faker->sha256());

        try {
            $validator->validate(
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
