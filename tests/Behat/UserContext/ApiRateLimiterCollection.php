<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * @psalm-api
 */
final readonly class ApiRateLimiterCollection
{
    public function __construct(
        public RateLimiterFactory $globalApiAnonymousLimiter,
        public RateLimiterFactory $globalApiAuthenticatedLimiter,
        public RateLimiterFactory $registrationLimiter,
        public RateLimiterFactory $emailConfirmationLimiter,
        public RateLimiterFactory $userCollectionLimiter,
        public RateLimiterFactory $userUpdateLimiter,
        public RateLimiterFactory $userDeleteLimiter,
        public RateLimiterFactory $resendConfirmationLimiter,
        public RateLimiterFactory $resendConfirmationTargetLimiter,
    ) {
    }
}
