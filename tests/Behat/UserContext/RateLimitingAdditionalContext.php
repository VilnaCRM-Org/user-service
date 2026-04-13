<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Behat\Behat\Context\Context;
use RuntimeException;

final readonly class RateLimitingAdditionalContext implements Context
{
    public function __construct(
        private UserOperationsState $state,
        private UserRepositoryInterface $userRepository,
        private \Symfony\Component\RateLimiter\RateLimiterFactory $passwordResetConfirmLimiter,
        private \Symfony\Component\RateLimiter\RateLimiterFactory $recoveryCodesLimiter,
        private \Symfony\Component\RateLimiter\RateLimiterFactory $signoutLimiter,
        private \Symfony\Component\RateLimiter\RateLimiterFactory $signoutAllLimiter,
        private RateLimiterTestHelper $rateLimiterTestHelper,
    ) {
    }

    /**
     * @Given :count password reset confirm requests from the same IP have been sent within 1 minute
     */
    public function passwordResetConfirmRequestsFromTheSameIpWithinMinute(int $count): void
    {
        $this->rateLimiterTestHelper->consumeLoopbackLimiter(
            $this->passwordResetConfirmLimiter,
            $this->state,
            $count
        );
    }

    /**
     * @Given :count recovery code regeneration requests have been sent within 1 minute
     */
    public function recoveryCodeRegenerationRequestsHaveBeenSentWithinMinute(int $count): void
    {
        $this->rateLimiterTestHelper->consume(
            $this->recoveryCodesLimiter,
            $this->rateLimiterTestHelper->buildUserKey($this->resolveCurrentUserId()),
            $count
        );
    }

    /**
     * @Given :count signout requests have been sent within 1 minute
     */
    public function signoutRequestsHaveBeenSentWithinMinute(int $count): void
    {
        $this->rateLimiterTestHelper->consume(
            $this->signoutLimiter,
            $this->rateLimiterTestHelper->buildUserKey($this->resolveCurrentUserId()),
            $count
        );
    }

    /**
     * @Given :count signout-all requests have been sent within 1 minute
     */
    public function signoutAllRequestsHaveBeenSentWithinMinute(int $count): void
    {
        $this->rateLimiterTestHelper->consume(
            $this->signoutAllLimiter,
            $this->rateLimiterTestHelper->buildUserKey($this->resolveCurrentUserId()),
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

}
