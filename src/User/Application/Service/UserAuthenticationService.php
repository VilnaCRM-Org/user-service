<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\User\Domain\Contract\AccountLockoutServiceInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * @psalm-api
 */
final class UserAuthenticationService implements UserAuthenticationServiceInterface
{
    private const LOCKOUT_MESSAGE = 'Account temporarily locked';
    private const DUMMY_PASSWORD = 'user-auth-dummy-password';

    private string $dummyPasswordHash;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherFactoryInterface $hasherFactory,
        private readonly AccountLockoutServiceInterface $lockoutService,
        private readonly SignInEventPublisherInterface $eventPublisher,
        ?string $dummyPasswordHash = null,
    ) {
        $this->dummyPasswordHash = $this->resolveDummyHash($dummyPasswordHash);
    }

    #[\Override]
    public function authenticate(
        string $email,
        string $password,
        string $ipAddress,
        string $userAgent
    ): User {
        $email = $this->normalizeEmail($email);

        $this->assertNotLocked($email);

        $found = $this->userRepository->findByEmail($email);
        $user = $found instanceof User ? $found : null;

        $verified = $this->verifyCredentials($user, $password);
        if ($verified === null) {
            $this->handleFailure($email, $ipAddress, $userAgent);
        }

        $this->lockoutService->clearFailures($email);
        return $verified;
    }

    private function assertNotLocked(string $email): void
    {
        if (!$this->lockoutService->isLocked($email)) {
            return;
        }

        $this->publishLockedOut($email);
        throw $this->lockedException();
    }

    private function verifyCredentials(?User $user, string $password): ?User
    {
        $hasher = $this->hasherFactory->getPasswordHasher(User::class);

        if (!$user instanceof User) {
            $hasher->verify($this->dummyPasswordHash, $password);

            return null;
        }

        return $hasher->verify($user->getPassword(), $password) ? $user : null;
    }

    private function handleFailure(
        string $email,
        string $ipAddress,
        string $userAgent
    ): never {
        $lockedAfterFailure = $this->lockoutService->recordFailure($email);

        $this->eventPublisher->publishFailed(
            $email,
            $ipAddress,
            $userAgent,
            'invalid_credentials'
        );

        if ($lockedAfterFailure) {
            $this->publishLockedOut($email);
            throw $this->lockedException();
        }

        throw new UnauthorizedHttpException('Bearer', 'Invalid credentials.');
    }

    private function publishLockedOut(string $email): void
    {
        $this->eventPublisher->publishLockedOut(
            $email,
            AccountLockoutServiceInterface::MAX_ATTEMPTS,
            AccountLockoutServiceInterface::LOCKOUT_SECONDS
        );
    }

    private function lockedException(): LockedHttpException
    {
        return new LockedHttpException(
            self::LOCKOUT_MESSAGE,
            null,
            0,
            ['Retry-After' => (string) AccountLockoutServiceInterface::LOCKOUT_SECONDS]
        );
    }

    private function resolveDummyHash(?string $dummyPasswordHash): string
    {
        if (is_string($dummyPasswordHash) && $dummyPasswordHash !== '') {
            return $dummyPasswordHash;
        }

        return $this->hasherFactory->getPasswordHasher(User::class)->hash(self::DUMMY_PASSWORD);
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
