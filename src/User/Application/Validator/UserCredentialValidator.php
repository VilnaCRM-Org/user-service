<?php

declare(strict_types=1);

namespace App\User\Application\Validator;

use App\User\Application\Provider\AccountLockoutProviderInterface;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Publisher\SignInPublisherInterface;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @psalm-api
 */
final class UserCredentialValidator implements UserCredentialValidatorInterface
{
    private const LOCKOUT_MESSAGE = 'Account temporarily locked';
    private const DUMMY_PASSWORD = 'user-auth-dummy-password';

    private string $dummyPasswordHash;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly AccountLockoutProviderInterface $lockoutGuard,
        private readonly SignInPublisherInterface $signInPublisher,
        ?string $dummyPasswordHash = null,
    ) {
        $this->dummyPasswordHash = is_string($dummyPasswordHash) && $dummyPasswordHash !== ''
            ? $dummyPasswordHash
            : $this->passwordHasher->hash(self::DUMMY_PASSWORD);
    }

    #[\Override]
    public function validate(
        string $email,
        string $password,
        string $ipAddress,
        string $userAgent
    ): User {
        $normalizedEmail = strtolower(trim($email));

        $this->assertNotLocked($normalizedEmail);

        $found = $this->userRepository->findByEmail($normalizedEmail);
        $user = $found instanceof User ? $found : null;

        $verified = $this->verifyCredentials($user, $password);
        if (!$verified instanceof User) {
            $this->handleFailure($normalizedEmail, $ipAddress, $userAgent);
        }

        if ($this->passwordHasher->needsRehash($verified->getPassword())) {
            $verified->upgradePasswordHash($this->passwordHasher->hash($password));
            $this->userRepository->save($verified);
        }

        $this->lockoutGuard->clearFailures($normalizedEmail);

        return $verified;
    }

    private function assertNotLocked(string $email): void
    {
        if (!$this->lockoutGuard->isLocked($email)) {
            return;
        }

        $this->throwLockedException($email);
    }

    private function verifyCredentials(?User $user, string $password): ?User
    {
        if (!$user instanceof User) {
            $this->passwordHasher->verify($this->dummyPasswordHash, $password);

            return null;
        }

        return $this->passwordHasher->verify($user->getPassword(), $password)
            ? $user
            : null;
    }

    private function handleFailure(
        string $email,
        string $ipAddress,
        string $userAgent
    ): never {
        $lockedAfterFailure = $this->lockoutGuard->recordFailure($email);

        $this->signInPublisher->publishFailed(
            $email,
            $ipAddress,
            $userAgent,
            'invalid_credentials'
        );

        if ($lockedAfterFailure) {
            $this->throwLockedException($email);
        }

        throw new UnauthorizedHttpException('Bearer', 'Invalid credentials.');
    }

    private function throwLockedException(string $email): never
    {
        $maxAttempts = $this->lockoutGuard->maxAttempts();
        $lockoutSeconds = $this->lockoutGuard->lockoutSeconds();
        $this->signInPublisher->publishLockedOut($email, $maxAttempts, $lockoutSeconds);

        throw new LockedHttpException(
            self::LOCKOUT_MESSAGE,
            null,
            0,
            ['Retry-After' => (string) $lockoutSeconds]
        );
    }
}
