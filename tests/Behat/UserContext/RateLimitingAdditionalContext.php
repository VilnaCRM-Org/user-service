<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Behat\Behat\Context\Context;
use RuntimeException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class RateLimitingAdditionalContext implements Context
{
    private const LOOPBACK_IPS = ['127.0.0.1', '::1'];

    public function __construct(
        private UserOperationsState $state,
        private UserRepositoryInterface $userRepository,
        private RateLimiterFactory $passwordResetConfirmLimiter,
        private RateLimiterFactory $recoveryCodesLimiter,
        private RateLimiterFactory $signoutLimiter,
        private RateLimiterFactory $signoutAllLimiter,
        private RedisDatabaseMirror $redisDatabaseMirror,
    ) {
    }

    /**
     * @Given :count password reset confirm requests from the same IP have been sent within 1 minute
     */
    public function passwordResetConfirmRequestsFromTheSameIpWithinMinute(int $count): void
    {
        $this->consumeLoopbackLimiter($this->passwordResetConfirmLimiter, $count);
    }

    /**
     * @Given :count recovery code regeneration requests have been sent within 1 minute
     */
    public function recoveryCodeRegenerationRequestsHaveBeenSentWithinMinute(int $count): void
    {
        $this->consume(
            $this->recoveryCodesLimiter,
            $this->buildUserKey($this->resolveCurrentUserId()),
            $count
        );
    }

    /**
     * @Given :count signout requests have been sent within 1 minute
     */
    public function signoutRequestsHaveBeenSentWithinMinute(int $count): void
    {
        $this->consume(
            $this->signoutLimiter,
            $this->buildUserKey($this->resolveCurrentUserId()),
            $count
        );
    }

    /**
     * @Given :count signout-all requests have been sent within 1 minute
     */
    public function signoutAllRequestsHaveBeenSentWithinMinute(int $count): void
    {
        $this->consume(
            $this->signoutAllLimiter,
            $this->buildUserKey($this->resolveCurrentUserId()),
            $count
        );
    }

    private function resolveCurrentUserId(): string
    {
        $currentUserEmail = $this->state->currentUserEmail;
        if (!is_string($currentUserEmail) || $currentUserEmail === '') {
            throw new RuntimeException('Current user email is not available in scenario state.');
        }

        $user = $this->userRepository->findByEmail($currentUserEmail);
        if (!$user instanceof User) {
            throw new RuntimeException(
                sprintf('User with email %s was not found.', $currentUserEmail)
            );
        }

        return $user->getId();
    }

    private function consume(RateLimiterFactory $limiter, string $key, int $count): void
    {
        for ($i = 0; $i < $count; ++$i) {
            $limiter->create($key)->consume();
        }
        $this->redisDatabaseMirror->mirrorDefaultLimiterStateToHttpDatabase();
    }

    private function consumeLoopbackLimiter(RateLimiterFactory $limiter, int $count): void
    {
        foreach ($this->loopbackIpKeys() as $loopbackIpKey) {
            $this->consume($limiter, $loopbackIpKey, $count);
        }
    }

    private function buildUserKey(string $userId): string
    {
        return sprintf('user:%s', $userId);
    }

    private function buildIpKey(): string
    {
        $clientIpAddress = self::LOOPBACK_IPS[0];
        $this->state->clientIpAddress = $clientIpAddress;

        return sprintf('ip:%s', $clientIpAddress);
    }

    /**
     * @return list<string>
     */
    private function loopbackIpKeys(): array
    {
        $this->state->clientIpAddress = self::LOOPBACK_IPS[0];

        return array_map(
            static fn (string $loopbackIp): string => sprintf('ip:%s', $loopbackIp),
            self::LOOPBACK_IPS
        );
    }
}
