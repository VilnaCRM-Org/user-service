<?php

declare(strict_types=1);

namespace App\User\Application\Authenticator;

use App\User\Application\EventPublisher\SignInEventsInterface;
use App\User\Application\Hasher\PasswordHasherInterface;
use App\User\Application\Lockout\AccountLockoutServiceInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @psalm-api
 */
final class UserAuthenticator implements UserAuthenticatorInterface
{
    private const LOCKOUT_MESSAGE = 'Account temporarily locked';
    private const DUMMY_PASSWORD = 'user-auth-dummy-password';

    private string $dummyPasswordHash;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly AccountLockoutServiceInterface $lockoutService,
        private readonly SignInEventsInterface $events,
        ?string $dummyPasswordHash = null,
    ) {
        $this->dummyPasswordHash = is_string($dummyPasswordHash) && $dummyPasswordHash !== ''
            ? $dummyPasswordHash
            : $this->passwordHasher->hash(self::DUMMY_PASSWORD);
    }

    #[\Override]
    public function authenticate(
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

        $this->lockoutService->clearFailures($normalizedEmail);

        return $verified;
    }

    private function assertNotLocked(string $email): void
    {
        if (!$this->lockoutService->isLocked($email)) {
            return;
        }

        $maxAttempts = $this->lockoutService->maxAttempts();
        $lockoutSeconds = $this->lockoutService->lockoutSeconds();

        $this->events->publishLockedOut($email, $maxAttempts, $lockoutSeconds);

        throw new LockedHttpException(
            self::LOCKOUT_MESSAGE,
            null,
            0,
            ['Retry-After' => (string) $lockoutSeconds]
        );
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
        $lockedAfterFailure = $this->lockoutService->recordFailure($email);

        $this->events->publishFailed($email, $ipAddress, $userAgent, 'invalid_credentials');

        if ($lockedAfterFailure) {
            $maxAttempts = $this->lockoutService->maxAttempts();
            $lockoutSeconds = $this->lockoutService->lockoutSeconds();
            $this->events->publishLockedOut(
                $email,
                $maxAttempts,
                $lockoutSeconds
            );

            throw new LockedHttpException(
                self::LOCKOUT_MESSAGE,
                null,
                0,
                ['Retry-After' => (string) $lockoutSeconds]
            );
        }

        throw new UnauthorizedHttpException('Bearer', 'Invalid credentials.');
    }
}
