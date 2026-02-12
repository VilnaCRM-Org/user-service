<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;

/** @infection-ignore-all */
final readonly class ApiRateLimitAuthTargetResolver
{
    private const SIGNIN_PATH = '/api/signin';
    private const SIGNIN_TWO_FACTOR_PATH = '/api/signin/2fa';
    private const TWO_FACTOR_ROUTE_LIMITERS = [
        '/api/users/2fa/setup' => 'twofa_setup',
        '/api/users/2fa/confirm' => 'twofa_confirm',
        '/api/users/2fa/disable' => 'twofa_disable',
    ];

    public function __construct(
        private ?PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository = null,
        private ApiRateLimitClientIdentityResolver $clientIdentityResolver =
            new ApiRateLimitClientIdentityResolver(),
    ) {
    }

    /**
     * @return string[][]
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
            $this->resolveAuthenticatedTwoFactorLimiters($request, $path, $method),
        );
    }

    /**
     * @return string[][]
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
     * @return string[][]
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

        $targets = [
            ['name' => 'twofa_verification_ip', 'key' => $this->buildIpKey($request)],
        ];

        $pendingSessionId = $this->clientIdentityResolver->resolvePendingSessionId($request);
        if ($pendingSessionId === null || $this->pendingTwoFactorRepository === null) {
            return $targets;
        }

        $pendingSession = $this->pendingTwoFactorRepository->findById($pendingSessionId);
        $userId = $pendingSession?->getUserId();
        if (is_string($userId) && $userId !== '') {
            $targets[] = [
                'name' => 'twofa_verification_user',
                'key' => $this->buildUserKey($userId),
            ];
        }

        return $targets;
    }

    /**
     * @return string[][]
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
