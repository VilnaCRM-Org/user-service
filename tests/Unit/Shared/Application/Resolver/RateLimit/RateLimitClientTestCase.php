<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Converter\JwtTokenConverterInterface;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitAuthTargetResolver;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitClientIdentityResolver;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitPayloadValueResolver;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitRequestResolver;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;

abstract class RateLimitClientTestCase extends UnitTestCase
{
    protected JwtTokenConverterInterface $jwtConverter;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtConverter = $this->createMock(JwtTokenConverterInterface::class);
    }

    /**
     * @param array<string, bool|float|int|string|null> $overrides
     *
     * @return array<string, array<int, string>|bool|float|int|string|null>
     */
    protected function buildValidPayload(array $overrides = []): array
    {
        $now = time();

        /** @var array<string, array<int, string>|bool|float|int|string|null> $base */
        $base = [
            'iss' => 'vilnacrm-user-service',
            'aud' => 'vilnacrm-api',
            'sub' => $this->faker->uuid(),
            'nbf' => $now - 60,
            'exp' => $now + 3600,
        ];

        return array_merge($base, $overrides);
    }

    protected function createClientIdentityResolver(
        ?JwtTokenConverterInterface $jwtConverter = null,
    ): ApiRateLimitClientIdentityResolver {
        return new ApiRateLimitClientIdentityResolver(
            new ApiRateLimitPayloadValueResolver($this->createJsonSerializer()),
            $jwtConverter,
        );
    }

    protected function createAuthTargetResolver(
        ?PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository = null,
        ?ApiRateLimitClientIdentityResolver $clientIdentityResolver = null,
    ): ApiRateLimitAuthTargetResolver {
        $resolver = $clientIdentityResolver ?? $this->createClientIdentityResolver();

        return new ApiRateLimitAuthTargetResolver(
            $pendingTwoFactorRepository,
            $resolver,
        );
    }

    protected function createRequestResolver(
        ?JwtTokenConverterInterface $jwtConverter = null,
        ?PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository = null,
    ): ApiRateLimitRequestResolver {
        $clientIdentityResolver = $this->createClientIdentityResolver($jwtConverter);

        return new ApiRateLimitRequestResolver(
            $clientIdentityResolver,
            $this->createAuthTargetResolver(
                $pendingTwoFactorRepository,
                $clientIdentityResolver,
            ),
        );
    }
}
