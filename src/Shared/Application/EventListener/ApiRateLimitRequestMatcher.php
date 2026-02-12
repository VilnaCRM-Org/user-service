<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use Symfony\Component\HttpFoundation\Request;

/** @infection-ignore-all */
final readonly class ApiRateLimitRequestMatcher
{
    public function __construct(
        private ApiRateLimitClientIdentityResolver $clientIdentityResolver =
            new ApiRateLimitClientIdentityResolver(),
        private ApiRateLimitAuthTargetResolver $authTargetResolver =
            new ApiRateLimitAuthTargetResolver(),
    ) {
    }

    public function supports(Request $request): bool
    {
        return str_starts_with($request->getPathInfo(), '/api/');
    }

    /**
     * @return string[]
     *
     * @psalm-return array{name: 'global_api_anonymous'|'global_api_authenticated', key: string}
     */
    public function resolveGlobalLimiter(Request $request): array
    {
        return [
            'name' => $this->clientIdentityResolver->isAuthenticatedRequest($request)
                ? 'global_api_authenticated'
                : 'global_api_anonymous',
            'key' => $this->buildIpKey($request),
        ];
    }

    /**
     * @return list<array{name: string, key: string}>
     */
    public function resolveEndpointLimiters(Request $request): array
    {
        $path = $request->getPathInfo();
        $method = strtoupper($request->getMethod());
        $targets = array_values(array_filter([
            $this->resolveRegistrationLimiter($request, $path, $method),
            $this->resolveTokenExchangeLimiter($request, $path, $method),
            $this->resolveEmailConfirmationLimiter($request, $path, $method),
            $this->resolveUserCollectionLimiter($request, $path, $method),
        ]));
        $this->appendTargets($targets, $this->resolveUserMutationLimiters($path, $method));
        $this->appendTargets(
            $targets,
            $this->resolveResendConfirmationLimiters($request, $path, $method)
        );
        $this->appendTargets($targets, $this->authTargetResolver->resolve($request));

        return $targets;
    }

    /**
     * @param list<array{name: string, key: string}> $targets
     * @param list<array{name: string, key: string}> $newTargets
     */
    private function appendTargets(array &$targets, array $newTargets): void
    {
        foreach ($newTargets as $target) {
            $targets[] = $target;
        }
    }

    /**
     * @return string[][]
     *
     * @psalm-return list{0?: array{name: 'user_delete'|'user_update', key: string}}
     */
    private function resolveUserMutationLimiters(
        string $path,
        string $method
    ): array {
        $userId = $this->resolveUserIdFromItemRoute($path);
        if ($userId === null) {
            return [];
        }

        if (in_array($method, ['PATCH', 'PUT'], true)) {
            return [['name' => 'user_update', 'key' => $this->buildUserKey($userId)]];
        }

        if ($method === 'DELETE') {
            return [['name' => 'user_delete', 'key' => $this->buildUserKey($userId)]];
        }

        return [];
    }

    /**
     * @return string[][]
     *
     * @psalm-return list{0?: array{name: 'resend_confirmation', key: string}, 1?: array{name: 'resend_confirmation_target', key: string}}
     */
    private function resolveResendConfirmationLimiters(
        Request $request,
        string $path,
        string $method
    ): array {
        $targetUserId = $this->resolveResendConfirmationTargetUserId($path, $method);
        if ($targetUserId === null) {
            return [];
        }

        return [
            ['name' => 'resend_confirmation', 'key' => $this->buildIpKey($request)],
            ['name' => 'resend_confirmation_target', 'key' => $this->buildUserKey($targetUserId)],
        ];
    }

    /**
     * @return null|string[]
     *
     * @psalm-return array{name: 'registration', key: string}|null
     */
    private function resolveRegistrationLimiter(
        Request $request,
        string $path,
        string $method
    ): array|null {
        if (
            $method === 'POST'
            && preg_match('#^/api/users(?:\.[^/]+)?$#', $path) === 1
        ) {
            return ['name' => 'registration', 'key' => $this->buildIpKey($request)];
        }

        return null;
    }

    /**
     * @return null|string[]
     *
     * @psalm-return array{name: 'oauth_token', key: string}|null
     */
    private function resolveTokenExchangeLimiter(
        Request $request,
        string $path,
        string $method
    ): array|null {
        if (
            $method === 'POST'
            && in_array($path, ['/api/token', '/api/oauth/token'], true)
        ) {
            return [
                'name' => 'oauth_token',
                'key' => $this->buildClientKey(
                    $this->clientIdentityResolver->resolveClientId($request)
                ),
            ];
        }

        return null;
    }

    /**
     * @return null|string[]
     *
     * @psalm-return array{name: 'email_confirmation', key: string}|null
     */
    private function resolveEmailConfirmationLimiter(
        Request $request,
        string $path,
        string $method
    ): array|null {
        if ($method === 'PATCH' && $path === '/api/users/confirm') {
            return ['name' => 'email_confirmation', 'key' => $this->buildIpKey($request)];
        }

        return null;
    }

    /**
     * @return null|string[]
     *
     * @psalm-return array{name: 'user_collection', key: string}|null
     */
    private function resolveUserCollectionLimiter(
        Request $request,
        string $path,
        string $method
    ): array|null {
        if (
            $method === 'GET'
            && preg_match('#^/api/users(?:\.[^/]+)?$#', $path) === 1
        ) {
            return ['name' => 'user_collection', 'key' => $this->buildIpKey($request)];
        }

        return null;
    }

    private function resolveUserIdFromItemRoute(string $path): ?string
    {
        if (preg_match('#^/api/users/([^/.]+)(?:\.[^/]+)?$#', $path, $matches) !== 1) {
            return null;
        }

        $id = $matches[1];
        if (in_array($id, ['batch', 'confirm'], true)) {
            return null;
        }

        return $id;
    }

    private function resolveResendConfirmationTargetUserId(
        string $path,
        string $method
    ): ?string {
        if ($method !== 'POST') {
            return null;
        }

        if (
            preg_match(
                '#^/api/users/([^/]+)/resend-confirmation-email$#',
                $path,
                $matches
            ) !== 1
        ) {
            return null;
        }

        return $matches[1];
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

    private function buildClientKey(string $clientId): string
    {
        return sprintf('client:%s', $clientId);
    }
}
