<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\RateLimit;

use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class ApiRateLimitAuthTargetResolver
{
    private const SIGNIN_PATH = '/api/signin';
    private const SIGNIN_TWO_FACTOR_PATH = '/api/signin/2fa';
    private const GRAPHQL_PATH = '/api/graphql';
    private const GRAPHQL_TWO_FACTOR_OPERATION = 'completeTwoFactor';
    private const TWO_FACTOR_ROUTE_LIMITERS = [
        '/api/2fa/setup' => 'twofa_setup',
        '/api/2fa/confirm' => 'twofa_confirm',
        '/api/2fa/disable' => 'twofa_disable',
    ];

    public function __construct(
        private ?PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository,
        private ApiRateLimitClientIdentityResolver $clientIdentityResolver,
    ) {
    }

    /**
     * @return array<array<string>>
     *
     * @psalm-return list<array{key: string, name: 'signin_email'|'signin_ip'|'twofa_confirm'|'twofa_disable'|'twofa_setup'|'twofa_verification_ip'|'twofa_verification_user'}>
     */
    public function resolve(Request $request): array
    {
        $path = $request->getPathInfo();
        $method = strtoupper($request->getMethod());

        return array_merge(
            $this->resolveSignInLimiters($request, $path, $method),
            $this->resolveSignInTwoFactorLimiters($request, $path, $method),
            $this->resolveGraphQlTwoFactorLimiters($request, $path, $method),
            $this->resolveAuthenticatedTwoFactorLimiters($request, $path, $method),
        );
    }

    /**
     * @return array<array<string>>
     *
     * @psalm-return list{0?: array{name: 'signin_ip', key: string}, 1?: array{name: 'signin_email', key: string}}
     */
    private function resolveSignInLimiters(
        Request $request,
        string $path,
        string $method
    ): array {
        if ($method !== 'POST' || $path !== self::SIGNIN_PATH) {
            return [];
        }

        $targets = [
            ['name' => 'signin_ip', 'key' => $this->buildIpKey($request)],
        ];

        $email = $this->clientIdentityResolver->resolveSignInEmail($request);
        if ($email !== null) {
            $targets[] = ['name' => 'signin_email', 'key' => $this->buildEmailKey($email)];
        }

        return $targets;
    }

    /**
     * @return array<array<string>>
     *
     * @psalm-return list{0?: array{name: 'twofa_verification_ip', key: string}, 1?: array{name: 'twofa_verification_user', key: string}}
     */
    private function resolveSignInTwoFactorLimiters(
        Request $request,
        string $path,
        string $method
    ): array {
        if ($method !== 'POST' || $path !== self::SIGNIN_TWO_FACTOR_PATH) {
            return [];
        }

        return $this->buildTwoFactorVerificationTargets($request);
    }

    /**
     * Applies the same per-IP/per-user 2FA verification limiters to the GraphQL
     * `completeTwoFactor` mutation so it cannot bypass throttling via /api/graphql.
     *
     * @return array<array<string>>
     *
     * @psalm-return list{0?: array{name: 'twofa_verification_ip', key: string}, 1?: array{name: 'twofa_verification_user', key: string}}
     */
    private function resolveGraphQlTwoFactorLimiters(
        Request $request,
        string $path,
        string $method
    ): array {
        if ($method !== 'POST' || $path !== self::GRAPHQL_PATH) {
            return [];
        }

        if (!$this->isCompleteTwoFactorMutation($request)) {
            return [];
        }

        return $this->buildTwoFactorVerificationTargets($request);
    }

    /**
     * @return array<array<string>>
     *
     * @psalm-return list{0: array{name: 'twofa_verification_ip', key: string}, 1?: array{name: 'twofa_verification_user', key: string}}
     */
    private function buildTwoFactorVerificationTargets(Request $request): array
    {
        $targets = [
            ['name' => 'twofa_verification_ip', 'key' => $this->buildIpKey($request)],
        ];

        $userId = $this->resolveTwoFactorUserId($request);
        if ($userId !== null) {
            $targets[] = [
                'name' => 'twofa_verification_user',
                'key' => $this->buildUserKey($userId),
            ];
        }

        return $targets;
    }

    private function resolveTwoFactorUserId(Request $request): ?string
    {
        $pendingSessionId = $this->clientIdentityResolver->resolvePendingSessionId($request);
        if ($pendingSessionId === null || $this->pendingTwoFactorRepository === null) {
            return null;
        }

        $userId = $this->pendingTwoFactorRepository->findById($pendingSessionId)?->getUserId();

        return is_string($userId) && $userId !== '' ? $userId : null;
    }

    private function isCompleteTwoFactorMutation(Request $request): bool
    {
        $query = $this->clientIdentityResolver->resolvePayloadValue($request, ['query']);

        return is_string($query)
            && str_contains($query, self::GRAPHQL_TWO_FACTOR_OPERATION);
    }

    /**
     * @return array<array<string>>
     *
     * @psalm-return list{0?: array{name: 'twofa_confirm'|'twofa_disable'|'twofa_setup', key: string}}
     */
    private function resolveAuthenticatedTwoFactorLimiters(
        Request $request,
        string $path,
        string $method
    ): array {
        if ($method !== 'POST') {
            return [];
        }

        $limiter = self::TWO_FACTOR_ROUTE_LIMITERS[$path] ?? null;
        if (!is_string($limiter) || $limiter === '') {
            return [];
        }

        $subject = $this->clientIdentityResolver->resolveUserSubject($request);
        if ($subject === null) {
            return [];
        }

        return [['name' => $limiter, 'key' => $this->buildUserKey($subject)]];
    }

    private function buildIpKey(Request $request): string
    {
        $ipAddress = $request->getClientIp() ?? '0.0.0.0';
        return sprintf('ip:%s', $ipAddress);
    }

    private function buildUserKey(string $userId): string
    {
        return sprintf('user:%s', $userId);
    }

    private function buildEmailKey(string $email): string
    {
        return sprintf('email:%s', $email);
    }
}
