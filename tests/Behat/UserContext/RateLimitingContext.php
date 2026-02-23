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
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 */
final readonly class RateLimitingContext implements Context
{
    use RateLimitingContextHelperTrait;

    private const IP_ADDRESS = '127.0.0.1';
    private const DEFAULT_PENDING_SESSION_ID = 'some-session';

    public function __construct(
        private UserOperationsState $state,
        private UserRepositoryInterface $userRepository,
        private PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository,
        private ContainerInterface $container,
    ) {
    }

    /**
     * @Given :count anonymous requests have been sent within 1 minute
     */
    public function anonymousRequestsHaveBeenSentWithinMinute(int $count): void
    {
        $this->consume($this->limiter('global_api_anonymous'), $this->buildIpKey(), $count);
    }

    /**
     * @Given :count authenticated requests have been sent within 1 minute
     */
    public function authenticatedRequestsHaveBeenSentWithinMinute(int $count): void
    {
        $this->consume($this->limiter('global_api_authenticated'), $this->buildIpKey(), $count);
    }

    /**
     * @Given :count registration requests have been sent from the same IP within 1 minute
     */
    public function registrationRequestsHaveBeenSentFromSameIpWithinMinute(int $count): void
    {
        $this->consume($this->limiter('registration'), $this->buildIpKey(), $count);
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

        $this->consume($this->limiter('user_collection'), $this->buildIpKey(), $count);
    }

    /**
     * @Given :count PATCH requests for the user have been sent within 1 minute
     */
    public function patchRequestsForCurrentUserHaveBeenSentWithinMinute(int $count): void
    {
        $this->consume(
            $this->limiter('user_update'),
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
        $this->consume($this->limiter('user_update'), $this->buildUserKey($userId), $count);
    }

    /**
     * @Given :count DELETE requests for the user have been sent within 1 minute
     */
    public function deleteRequestsForCurrentUserHaveBeenSentWithinMinute(int $count): void
    {
        $this->consume(
            $this->limiter('user_delete'),
            $this->buildUserKey($this->requireCurrentUserId()),
            $count
        );
    }

    /**
     * @Given :count resend confirmation requests from the same IP have been sent within 1 minute
     */
    public function resendConfirmationRequestsFromSameIpWithinMinute(int $count): void
    {
        $this->consume($this->limiter('resend_confirmation'), $this->buildIpKey(), $count);
    }

    /**
     * @Given :count resend confirmation requests targeting user :userId have been sent within 1 minute
     */
    public function resendConfirmationRequestsTargetingUserWithinMinute(
        int $count,
        string $userId
    ): void {
        $this->consume(
            $this->limiter('resend_confirmation_target'),
            $this->buildUserKey($userId),
            $count
        );
    }

    /**
     * @Given :count token exchange requests with the same client_id have been sent within 1 minute
     */
    public function tokenExchangeRequestsWithSameClientWithinMinute(int $count): void
    {
        $this->consume($this->limiter('oauth_token'), 'client:anonymous', $count);
    }

    /**
     * @Given :count email confirmation requests from the same IP have been sent within 1 minute
     */
    public function emailConfirmationRequestsFromSameIpWithinMinute(int $count): void
    {
        $this->consume($this->limiter('email_confirmation'), $this->buildIpKey(), $count);
    }

    /**
     * @Given :count sign-in requests from the same IP have been sent within 1 minute
     */
    public function signInRequestsFromSameIpWithinMinute(int $count): void
    {
        $this->consume($this->limiter('signin_ip'), $this->buildIpKey(), $count);
    }

    /**
     * @Given :count sign-in requests for email :email have been sent within 1 minute
     */
    public function signInRequestsForEmailWithinMinute(
        int $count,
        string $email
    ): void {
        $this->consume($this->limiter('signin_email'), $this->buildEmailKey($email), $count);
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
            $this->limiter('twofa_verification_user'),
            $this->buildUserKey($userId),
            $count
        );
    }

    /**
     * @Given :count two-factor verification requests from the same IP have been sent within 1 minute
     */
    public function twoFactorVerificationRequestsFromSameIpWithinMinute(int $count): void
    {
        $this->consume($this->limiter('twofa_verification_ip'), $this->buildIpKey(), $count);
    }

    /**
     * @Given :count two-factor setup requests have been sent within 1 minute
     */
    public function twoFactorSetupRequestsWithinMinute(int $count): void
    {
        $this->consume(
            $this->limiter('twofa_setup'),
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
            $this->limiter('twofa_confirm'),
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
            $this->limiter('twofa_disable'),
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
        $this->consume($this->limiter('password_reset'), $email, $count);
    }

    /**
     * @Given the sign-in rate limit for IP has been exceeded
     */
    public function signInRateLimitForIpHasBeenExceeded(): void
    {
        $this->signInRequestsFromSameIpWithinMinute(10);
    }
}
