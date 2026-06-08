<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Validator;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Provider\AccountLockoutProviderInterface;
use App\User\Application\Query\FindUserByEmailQueryHandlerInterface;
use App\User\Application\Validator\UserCredentialValidator;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Exception\DuplicateEmailException;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Publisher\SignInPublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class UserCredentialValidatorDuplicateEmailTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private FindUserByEmailQueryHandlerInterface&MockObject $findUserByEmailQueryHandler;
    private PasswordHasherInterface&MockObject $passwordHasher;
    private AccountLockoutProviderInterface&MockObject $lockoutGuard;
    private SignInPublisherInterface&MockObject $events;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->findUserByEmailQueryHandler =
            $this->createMock(FindUserByEmailQueryHandlerInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->lockoutGuard = $this->createMock(AccountLockoutProviderInterface::class);
        $this->events = $this->createMock(SignInPublisherInterface::class);
    }

    public function testValidateTreatsDuplicateEmailLookupAsInvalidCredentials(): void
    {
        $credentials = $this->createCredentialFixture();

        $this->lockoutGuard->method('isLocked')->willReturn(false);
        $this->expectDuplicateEmailLookup($credentials['normalizedEmail']);
        $this->expectFailedCredentialHandling($credentials);
        $this->lockoutGuard->expects($this->never())->method('clearFailures');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid credentials.');

        $this->createValidator($credentials['dummyHash'])->validate(
            $credentials['email'],
            $credentials['password'],
            $credentials['ipAddress'],
            $credentials['userAgent']
        );
    }

    /**
     * @return array{
     *     email: string,
     *     normalizedEmail: string,
     *     password: string,
     *     ipAddress: string,
     *     userAgent: string,
     *     dummyHash: string
     * }
     */
    private function createCredentialFixture(): array
    {
        $email = $this->faker->email();

        return [
            'email' => $email,
            'normalizedEmail' => strtolower(trim($email)),
            'password' => $this->faker->password(),
            'ipAddress' => $this->faker->ipv4(),
            'userAgent' => $this->faker->userAgent(),
            'dummyHash' => $this->faker->sha256(),
        ];
    }

    private function expectDuplicateEmailLookup(string $normalizedEmail): void
    {
        $this->findUserByEmailQueryHandler->expects($this->once())
            ->method('find')
            ->with($normalizedEmail)
            ->willThrowException(new DuplicateEmailException($normalizedEmail));
    }

    /**
     * @param array{
     *     normalizedEmail: string,
     *     password: string,
     *     ipAddress: string,
     *     userAgent: string,
     *     dummyHash: string
     * } $credentials
     */
    private function expectFailedCredentialHandling(array $credentials): void
    {
        $this->passwordHasher->expects($this->once())
            ->method('verify')
            ->with($credentials['dummyHash'], $credentials['password'])
            ->willReturn(false);
        $this->lockoutGuard->expects($this->once())
            ->method('recordFailure')
            ->with($credentials['normalizedEmail'])
            ->willReturn(false);
        $this->events->expects($this->once())
            ->method('publishFailed')
            ->with(
                $credentials['normalizedEmail'],
                $credentials['ipAddress'],
                $credentials['userAgent'],
                'invalid_credentials'
            );
    }

    private function createValidator(string $dummyHash): UserCredentialValidator
    {
        return new UserCredentialValidator(
            $this->userRepository,
            $this->findUserByEmailQueryHandler,
            $this->passwordHasher,
            $this->lockoutGuard,
            $this->events,
            $dummyHash
        );
    }
}
