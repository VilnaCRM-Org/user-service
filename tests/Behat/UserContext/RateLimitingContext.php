<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\User\Domain\Entity\PendingTwoFactor;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Behat\Behat\Context\Context;
use DateTimeImmutable;
use RuntimeException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
final readonly class RateLimitingContext implements Context
{
    private const IP_ADDRESS = '127.0.0.1';
    private const DEFAULT_PENDING_SESSION_ID = 'some-session';

    /** @SuppressWarnings(PHPMD.ExcessiveParameterList) */
    public function __construct(
        private UserOperationsState $state,
        private UserRepositoryInterface $userRepository,
        private PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository,
        private RateLimiterFactory $globalApiAnonymousLimiter,
        private RateLimiterFactory $globalApiAuthenticatedLimiter,
        private RateLimiterFactory $registrationLimiter,
        private RateLimiterFactory $oauthTokenLimiter,
        private RateLimiterFactory $signinIpLimiter,
        private RateLimiterFactory $signinEmailLimiter,
        private RateLimiterFactory $twofaVerificationUserLimiter,
        private RateLimiterFactory $twofaVerificationIpLimiter,
        private RateLimiterFactory $twofaSetupLimiter,
        private RateLimiterFactory $twofaConfirmLimiter,
        private RateLimiterFactory $twofaDisableLimiter,
        private RateLimiterFactory $emailConfirmationLimiter,
        private RateLimiterFactory $userCollectionLimiter,
        private RateLimiterFactory $userUpdateLimiter,
        private RateLimiterFactory $userDeleteLimiter,
        private RateLimiterFactory $resendConfirmationLimiter,
        private RateLimiterFactory $resendConfirmationTargetLimiter,
        private RateLimiterFactory $passwordResetLimiter,
    ) {
    }

    /**
     * @Given :count anonymous requests have been sent within 1 minute
     */
    public function anonymousRequestsHaveBeenSentWithinMinute(int $count): void
    {
        $this->consume($this->globalApiAnonymousLimiter, $this->buildIpKey(), $count);
    }

    /**
     * @Given :count authenticated requests have been sent within 1 minute
     */
    public function authenticatedRequestsHaveBeenSentWithinMinute(int $count): void
    {
        $this->consume($this->globalApiAuthenticatedLimiter, $this->buildIpKey(), $count);
    }

    /**
     * @Given :count registration requests have been sent from the same IP within 1 minute
     */
    public function registrationRequestsHaveBeenSentFromSameIpWithinMinute(int $count): void
    {
        $this->consume($this->registrationLimiter, $this->buildIpKey(), $count);
    }

    /**
     * @Given :count GET requests to :path have been sent within 1 minute
     */
    public function getRequestsToPathHaveBeenSentWithinMinute(
        int $count,
        string $path
    ): void {
        if (!str_starts_with($path, '/api/users')) {
            throw new RuntimeException(sprintf('Unsupported rate-limit path: %s', $path));
        }

        $this->consume($this->userCollectionLimiter, $this->buildIpKey(), $count);
    }

    /**
     * @Given :count PATCH requests for the user have been sent within 1 minute
     */
    public function patchRequestsForCurrentUserHaveBeenSentWithinMinute(int $count): void
    {
        $this->consume(
            $this->userUpdateLimiter,
            $this->buildUserKey($this->requireCurrentUserId()),
            $count
        );
    }

    /**
     * @Given :count PATCH requests for user :userId have been sent within 1 minute
     */
    public function patchRequestsForSpecificUserHaveBeenSentWithinMinute(
        int $count,
        string $userId
    ): void {
        $this->consume($this->userUpdateLimiter, $this->buildUserKey($userId), $count);
    }

    /**
     * @Given :count DELETE requests for the user have been sent within 1 minute
     */
    public function deleteRequestsForCurrentUserHaveBeenSentWithinMinute(int $count): void
    {
        $this->consume(
            $this->userDeleteLimiter,
            $this->buildUserKey($this->requireCurrentUserId()),
            $count
        );
    }

    /**
     * @Given :count resend confirmation requests from the same IP have been sent within 1 minute
     */
    public function resendConfirmationRequestsFromSameIpWithinMinute(int $count): void
    {
        $this->consume($this->resendConfirmationLimiter, $this->buildIpKey(), $count);
    }

    /**
     * @Given :count resend confirmation requests targeting user :userId have been sent within 1 minute
     */
    public function resendConfirmationRequestsTargetingUserWithinMinute(
        int $count,
        string $userId
    ): void {
        $this->consume(
            $this->resendConfirmationTargetLimiter,
            $this->buildUserKey($userId),
            $count
        );
    }

    /**
     * @Given :count token exchange requests with the same client_id have been sent within 1 minute
     */
    public function tokenExchangeRequestsWithSameClientWithinMinute(int $count): void
    {
        $this->consume($this->oauthTokenLimiter, 'client:anonymous', $count);
    }

    /**
     * @Given :count email confirmation requests from the same IP have been sent within 1 minute
     */
    public function emailConfirmationRequestsFromSameIpWithinMinute(int $count): void
    {
        $this->consume($this->emailConfirmationLimiter, $this->buildIpKey(), $count);
    }

    /**
     * @Given :count sign-in requests from the same IP have been sent within 1 minute
     */
    public function signInRequestsFromSameIpWithinMinute(int $count): void
    {
        $this->consume($this->signinIpLimiter, $this->buildIpKey(), $count);
    }

    /**
     * @Given :count sign-in requests for email :email have been sent within 1 minute
     */
    public function signInRequestsForEmailWithinMinute(
        int $count,
        string $email
    ): void {
        $this->consume($this->signinEmailLimiter, $this->buildEmailKey($email), $count);
    }

    /**
     * @Given :count two-factor verification requests for user :email have been sent within 1 minute
     */
    public function twoFactorVerificationRequestsForUserWithinMinute(
        int $count,
        string $email
    ): void {
        $userId = $this->resolveUserIdByEmail($email);
        $this->pendingTwoFactorRepository->save(
            new PendingTwoFactor(
                self::DEFAULT_PENDING_SESSION_ID,
                $userId,
                new DateTimeImmutable('-10 seconds'),
                new DateTimeImmutable('+5 minutes')
            )
        );
        $this->state->pendingSessionId = self::DEFAULT_PENDING_SESSION_ID;

        $this->consume(
            $this->twofaVerificationUserLimiter,
            $this->buildUserKey($userId),
            $count
        );
    }

    /**
     * @Given :count two-factor verification requests from the same IP have been sent within 1 minute
     */
    public function twoFactorVerificationRequestsFromSameIpWithinMinute(int $count): void
    {
        $this->consume($this->twofaVerificationIpLimiter, $this->buildIpKey(), $count);
    }

    /**
     * @Given :count two-factor setup requests have been sent within 1 minute
     */
    public function twoFactorSetupRequestsWithinMinute(int $count): void
    {
        $this->consume(
            $this->twofaSetupLimiter,
            $this->buildUserKey($this->requireCurrentUserId()),
            $count
        );
    }

    /**
     * @Given :count two-factor confirm requests have been sent within 1 minute
     */
    public function twoFactorConfirmRequestsWithinMinute(int $count): void
    {
        $this->consume(
            $this->twofaConfirmLimiter,
            $this->buildUserKey($this->requireCurrentUserId()),
            $count
        );
    }

    /**
     * @Given :count two-factor disable requests have been sent within 1 minute
     */
    public function twoFactorDisableRequestsWithinMinute(int $count): void
    {
        $this->consume(
            $this->twofaDisableLimiter,
            $this->buildUserKey($this->requireCurrentUserId()),
            $count
        );
    }

    /**
     * @Given :count password reset requests for email :email have been sent within 1 hour
     */
    public function passwordResetRequestsForEmailWithinHour(
        int $count,
        string $email
    ): void {
        $this->consume($this->passwordResetLimiter, $email, $count);
    }

    /**
     * @Given the sign-in rate limit for IP has been exceeded
     */
    public function signInRateLimitForIpHasBeenExceeded(): void
    {
        $this->signInRequestsFromSameIpWithinMinute(10);
    }

    private function requireCurrentUserId(): string
    {
        $currentUserEmail = $this->state->currentUserEmail;
        if (!is_string($currentUserEmail) || $currentUserEmail === '') {
            throw new RuntimeException('Current user is not set.');
        }

        return $this->resolveUserIdByEmail($currentUserEmail);
    }

    private function resolveUserIdByEmail(string $email): string
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user instanceof User) {
            throw new RuntimeException(
                sprintf('User with email %s was not found.', $email)
            );
        }

        return $user->getId();
    }

    private function consume(
        RateLimiterFactory $limiter,
        string $key,
        int $count
    ): void {
        $limiter->create($key)->consume($count);
    }

    private function buildIpKey(): string
    {
        return sprintf('ip:%s', self::IP_ADDRESS);
    }

    private function buildUserKey(string $userId): string
    {
        return sprintf('user:%s', $userId);
    }

    private function buildEmailKey(string $email): string
    {
        return sprintf('email:%s', strtolower(trim($email)));
    }
}
