<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Converter\JwtTokenConverterInterface;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitAuthTargetResolver;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitClientIdentityResolver;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitGraphQlResolver;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitPayloadValueResolver;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitRequestResolver;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class RateLimitClientTestCase extends UnitTestCase
{
    protected const GRAPHQL_ENDPOINT = '/api/graphql';

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

    protected function createGraphQlResolver(
        ?ApiRateLimitClientIdentityResolver $clientIdentityResolver = null,
        ?PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository = null,
    ): ApiRateLimitGraphQlResolver {
        $resolver = $clientIdentityResolver ?? $this->createClientIdentityResolver();

        return new ApiRateLimitGraphQlResolver(
            $this->createJsonSerializer(),
            $resolver,
            $pendingTwoFactorRepository,
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
            $this->createGraphQlResolver(
                $clientIdentityResolver,
                $pendingTwoFactorRepository,
            ),
        );
    }

    protected function createRequestWithIp(
        string $path,
        string $method,
        string $clientIp
    ): Request {
        return Request::create($path, $method, [], [], [], ['REMOTE_ADDR' => $clientIp]);
    }

    /**
     * @return array<string, string>
     */
    protected function resolveEndpointLimiterKeysByName(
        ApiRateLimitRequestResolver $resolver,
        Request $request
    ): array {
        return array_column($resolver->resolveEndpointLimiters($request), 'key', 'name');
    }

    /**
     * @return list<string>
     */
    protected function resolveEndpointLimiterNames(
        ApiRateLimitRequestResolver $resolver,
        Request $request
    ): array {
        return array_column($resolver->resolveEndpointLimiters($request), 'name');
    }

    protected function createJsonPostRequest(
        string $path,
        string $body,
        string $clientIp = '127.0.0.1'
    ): Request {
        return Request::create(
            $path,
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp, 'CONTENT_TYPE' => 'application/json'],
            $body
        );
    }

    /**
     * @param array<string, array<string, scalar|null>|scalar|null> $variables
     */
    protected function createGraphQlRequest(
        string $query,
        array $variables = [],
        string $clientIp = '203.0.113.7'
    ): Request {
        $body = ['query' => $query];
        if ($variables !== []) {
            $body['variables'] = $variables;
        }

        return Request::create(
            self::GRAPHQL_ENDPOINT,
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp, 'CONTENT_TYPE' => 'application/json'],
            json_encode($body, JSON_THROW_ON_ERROR)
        );
    }
}
