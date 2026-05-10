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

final readonly class RateLimitingContext implements Context
{
    private const DEFAULT_PENDING_SESSION_ID = 'some-session';

    public function __construct(
        private UserOperationsState $state,
        private UserRepositoryInterface $userRepository,
        private PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository,
        private ApiRateLimiterCollection $apiLimiters,
        private AuthRateLimiterCollection $authLimiters,
        private RateLimiterTestHelper $rateLimiterTestHelper,
    ) {
    }

    /**
     * @Given :count anonymous requests have been sent within 1 minute
     */
    public function anonymousRequestsHaveBeenSentWithinMinute(int $count): void
    {
        $this->rateLimiterTestHelper->consumeLoopbackLimiter(
            $this->apiLimiters->globalApiAnonymousLimiter,
            $this->state,
            $count
        );
    }

    /**
     * @Given :count authenticated requests have been sent within 1 minute
     */
    public function authenticatedRequestsHaveBeenSentWithinMinute(int $count): void
    {
        $this->rateLimiterTestHelper->consumeLoopbackLimiter(
            $this->apiLimiters->globalApiAuthenticatedLimiter,
            $this->state,
            $count
        );
    }

    /**
     * @Given :count registration requests have been sent from the same IP within 1 minute
     */
    public function registrationRequestsHaveBeenSentFromSameIpWithinMinute(int $count): void
    {
        $this->rateLimiterTestHelper->consumeLoopbackLimiter(
            $this->apiLimiters->registrationLimiter,
            $this->state,
            $count
        );
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

        $this->rateLimiterTestHelper->consumeLoopbackLimiter(
            $this->apiLimiters->userCollectionLimiter,
            $this->state,
            $count
        );
    }

    /**
     * @Given :count PATCH requests for the user have been sent within 1 minute
     */
    public function patchRequestsForCurrentUserHaveBeenSentWithinMinute(int $count): void
    {
        $this->rateLimiterTestHelper->consume(
            $this->apiLimiters->userUpdateLimiter,
            $this->rateLimiterTestHelper->buildUserKey(
                $this->resolveUserIdByEmail(
                    (string) $this->state->currentUserEmail
                )
            ),
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
        $this->rateLimiterTestHelper->consume(
            $this->apiLimiters->userUpdateLimiter,
            $this->rateLimiterTestHelper->buildUserKey($userId),
            $count
        );
    }

    /**
     * @Given :count DELETE requests for the user have been sent within 1 minute
     */
    public function deleteRequestsForCurrentUserHaveBeenSentWithinMinute(int $count): void
    {
        $this->rateLimiterTestHelper->consume(
            $this->apiLimiters->userDeleteLimiter,
            $this->rateLimiterTestHelper->buildUserKey(
                $this->resolveUserIdByEmail(
                    (string) $this->state->currentUserEmail
                )
            ),
            $count
        );
    }

    /**
     * @Given :count resend confirmation requests from the same IP have been sent within 1 minute
     */
    public function resendConfirmationRequestsFromSameIpWithinMinute(int $count): void
    {
        $this->rateLimiterTestHelper->consumeLoopbackLimiter(
            $this->apiLimiters->resendConfirmationLimiter,
            $this->state,
            $count
        );
    }

    /**
     * @Given :count resend confirmation requests targeting user :userId have been sent within 1 minute
     */
    public function resendConfirmationRequestsTargetingUserWithinMinute(
        int $count,
        string $userId
    ): void {
        $this->rateLimiterTestHelper->consume(
            $this->apiLimiters->resendConfirmationTargetLimiter,
            $this->rateLimiterTestHelper->buildUserKey($userId),
            $count
        );
    }

    /**
     * @Given :count refresh token exchange requests from the same IP have been sent within 1 minute
     */
    public function refreshTokenExchangeRequestsFromSameIpWithinMinute(int $count): void
    {
        $this->rateLimiterTestHelper->consumeLoopbackLimiter(
            $this->authLimiters->refreshTokenLimiter,
            $this->state,
            $count
        );
    }

    /**
     * @Given :count email confirmation requests from the same IP have been sent within 1 minute
     */
    public function emailConfirmationRequestsFromSameIpWithinMinute(int $count): void
    {
        $this->rateLimiterTestHelper->consumeLoopbackLimiter(
            $this->apiLimiters->emailConfirmationLimiter,
            $this->state,
            $count
        );
    }

    /**
     * @Given :count sign-in requests from the same IP have been sent within 1 minute
     */
    public function signInRequestsFromSameIpWithinMinute(int $count): void
    {
        $this->rateLimiterTestHelper->consumeLoopbackLimiter(
            $this->authLimiters->signinIpLimiter,
            $this->state,
            $count
        );
    }

    /**
     * @Given :count sign-in requests for email :email have been sent within 1 minute
     */
    public function signInRequestsForEmailWithinMinute(
        int $count,
        string $email
    ): void {
        $this->rateLimiterTestHelper->consume(
            $this->authLimiters->signinEmailLimiter,
            $this->rateLimiterTestHelper->buildEmailKey($email),
            $count
        );
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

        $this->rateLimiterTestHelper->consume(
            $this->authLimiters->twofaVerificationUserLimiter,
            $this->rateLimiterTestHelper->buildUserKey($userId),
            $count
        );
    }

    /**
     * @Given :count two-factor verification requests from the same IP have been sent within 1 minute
     */
    public function twoFactorVerificationRequestsFromSameIpWithinMinute(int $count): void
    {
        $this->rateLimiterTestHelper->consumeLoopbackLimiter(
            $this->authLimiters->twofaVerificationIpLimiter,
            $this->state,
            $count
        );
    }

    /**
     * @Given :count two-factor setup requests have been sent within 1 minute
     */
    public function twoFactorSetupRequestsWithinMinute(int $count): void
    {
        $this->rateLimiterTestHelper->consume(
            $this->authLimiters->twofaSetupLimiter,
            $this->rateLimiterTestHelper->buildUserKey(
                $this->resolveUserIdByEmail(
                    (string) $this->state->currentUserEmail
                )
            ),
            $count
        );
    }

    /**
     * @Given :count two-factor confirm requests have been sent within 1 minute
     */
    public function twoFactorConfirmRequestsWithinMinute(int $count): void
    {
        $this->rateLimiterTestHelper->consume(
            $this->authLimiters->twofaConfirmLimiter,
            $this->rateLimiterTestHelper->buildUserKey(
                $this->resolveUserIdByEmail(
                    (string) $this->state->currentUserEmail
                )
            ),
            $count
        );
    }

    /**
     * @Given :count two-factor disable requests have been sent within 1 minute
     */
    public function twoFactorDisableRequestsWithinMinute(int $count): void
    {
        $this->rateLimiterTestHelper->consume(
            $this->authLimiters->twofaDisableLimiter,
            $this->rateLimiterTestHelper->buildUserKey(
                $this->resolveUserIdByEmail(
                    (string) $this->state->currentUserEmail
                )
            ),
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
        $this->rateLimiterTestHelper->consume(
            $this->authLimiters->passwordResetLimiter,
            $email,
            $count
        );
    }

    /**
     * @Given the sign-in rate limit for IP has been exceeded
     */
    public function signInRateLimitForIpHasBeenExceeded(): void
    {
        $this->signInRequestsFromSameIpWithinMinute(10);
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
}
