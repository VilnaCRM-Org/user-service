<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * @psalm-api
 */
final readonly class AuthRateLimiterCollection
{
    public function __construct(
        public RateLimiterFactory $signinIpLimiter,
        public RateLimiterFactory $signinEmailLimiter,
        public RateLimiterFactory $twofaVerificationUserLimiter,
        public RateLimiterFactory $twofaVerificationIpLimiter,
        public RateLimiterFactory $twofaSetupLimiter,
        public RateLimiterFactory $twofaConfirmLimiter,
        public RateLimiterFactory $twofaDisableLimiter,
        public RateLimiterFactory $oauthTokenLimiter,
        public RateLimiterFactory $passwordResetLimiter,
    ) {
    }
}
