<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\RateLimit;

use Symfony\Component\HttpFoundation\Request;

final readonly class ApiRateLimitGraphQlAuthTargetResolver
{
    private const GRAPHQL_PATH = '/api/graphql';
    private const REGISTRATION_MUTATIONS = [
        'createUser',
        'passkeySignUpOptionsUser',
        'passkeySignUpCompleteUser',
    ];
    private const SIGNIN_MUTATIONS = [
        'signInUser',
        'passkeySignInOptionsUser',
        'passkeySignInCompleteUser',
    ];

    public function __construct(
        private ApiRateLimitClientIdentityResolver $clientIdentityResolver,
    ) {
    }

    /**
     * @return list<array{name: 'registration'|'signin_email'|'signin_ip', key: string}>
     */
    public function resolve(Request $request): array
    {
        if (
            strtoupper($request->getMethod()) !== 'POST'
            || $request->getPathInfo() !== self::GRAPHQL_PATH
        ) {
            return [];
        }

        $targets = [];
        if ($this->containsMutation($request, self::REGISTRATION_MUTATIONS)) {
            $targets[] = ['name' => 'registration', 'key' => $this->buildIpKey($request)];
        }

        if ($this->containsMutation($request, self::SIGNIN_MUTATIONS)) {
            $targets[] = ['name' => 'signin_ip', 'key' => $this->buildIpKey($request)];

            $email = $this->clientIdentityResolver->resolveSignInEmail($request);
            if ($email !== null) {
                $targets[] = ['name' => 'signin_email', 'key' => $this->buildEmailKey($email)];
            }
        }

        return $targets;
    }

    /**
     * @param list<string> $mutationNames
     */
    private function containsMutation(Request $request, array $mutationNames): bool
    {
        $payload = $this->resolveGraphQlOperationPayload($request);
        foreach ($mutationNames as $mutationName) {
            if (preg_match('/\b' . $mutationName . '\b/', $payload) === 1) {
                return true;
            }
        }

        return false;
    }

    private function resolveGraphQlOperationPayload(Request $request): string
    {
        $payload = $request->getContent();
        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            return $payload;
        }

        $query = $decoded['query'] ?? null;
        if (!is_string($query)) {
            return $payload;
        }

        $operationName = $decoded['operationName'] ?? null;
        if (!is_string($operationName) || $operationName === '') {
            return $query;
        }

        return $this->extractNamedGraphQlOperation($query, $operationName);
    }

    private function extractNamedGraphQlOperation(string $query, string $operationName): string
    {
        if (preg_match_all(
            '/\b(?:mutation|query|subscription)\s+[A-Za-z_][A-Za-z0-9_]*\b/',
            $query,
            $matches,
            PREG_OFFSET_CAPTURE
        ) === 0) {
            return $query;
        }

        foreach ($matches[0] as $index => [$operation, $offset]) {
            if (!str_ends_with($operation, ' ' . $operationName)) {
                continue;
            }

            $next = $matches[0][$index + 1][1] ?? strlen($query);
            return substr($query, $offset, $next - $offset);
        }

        return $query;
    }

    private function buildIpKey(Request $request): string
    {
        $ipAddress = $request->getClientIp() ?? '0.0.0.0';
        return sprintf('ip:%s', $ipAddress);
    }

    private function buildEmailKey(string $email): string
    {
        return sprintf('email:%s', $email);
    }
}
