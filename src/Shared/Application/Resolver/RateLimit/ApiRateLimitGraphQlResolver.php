<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\RateLimit;

use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Maps sensitive GraphQL mutations to the same rate-limiter targets that protect
 * their REST counterparts so that POST /api/graphql cannot be used to bypass the
 * per-endpoint sign-in, refresh, 2FA and password-reset throttles.
 *
 * Security: CWE-799 - GraphQL endpoint bypasses per-endpoint rate limiters (#315).
 */
final readonly class ApiRateLimitGraphQlResolver
{
    private const GRAPHQL_PATH = '/api/graphql';
    private const MUTATION_FIELD_PATTERN =
        '/\b(signIn|refreshToken|completeTwoFactor|confirmPasswordReset|signOut|signOutAll)\b/';

    public function __construct(
        private SerializerInterface $serializer,
        private ApiRateLimitClientIdentityResolver $clientIdentityResolver,
        private ?PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository = null,
    ) {
    }

    /**
     * @return list<array{name: string, key: string}>
     */
    public function resolve(Request $request): array
    {
        if (!$this->isGraphQlMutationRequest($request)) {
            return [];
        }

        $payload = $this->decodePayload($request->getContent());
        if ($payload === null) {
            return [];
        }

        $query = $payload['query'] ?? null;
        if (!is_string($query)) {
            return [];
        }

        $input = $this->resolveInput($payload);

        return $this->resolveTargetsForMutations($request, $query, $input);
    }

    private function isGraphQlMutationRequest(Request $request): bool
    {
        return $request->getPathInfo() === self::GRAPHQL_PATH
            && strtoupper($request->getMethod()) === 'POST';
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return list<array{name: string, key: string}>
     */
    private function resolveTargetsForMutations(
        Request $request,
        string $query,
        array $input
    ): array {
        if (preg_match_all(self::MUTATION_FIELD_PATTERN, $query, $matches) === false) {
            return [];
        }

        $targets = [];
        foreach (array_unique($matches[1]) as $mutation) {
            foreach ($this->resolveMutationTargets($request, $mutation, $input) as $target) {
                $targets[] = $target;
            }
        }

        return $targets;
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return list<array{name: string, key: string}>
     */
    private function resolveMutationTargets(
        Request $request,
        string $mutation,
        array $input
    ): array {
        return match ($mutation) {
            'signIn' => $this->resolveSignInTargets($request, $input),
            'refreshToken' => [
                ['name' => 'refresh_token', 'key' => $this->buildIpKey($request)],
            ],
            'completeTwoFactor' => $this->resolveTwoFactorTargets($request, $input),
            'confirmPasswordReset' => [
                ['name' => 'password_reset_confirm', 'key' => $this->buildIpKey($request)],
            ],
            'signOut' => $this->resolveAuthenticatedTargets($request, 'signout'),
            'signOutAll' => $this->resolveAuthenticatedTargets($request, 'signout_all'),
            default => [],
        };
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return list<array{name: string, key: string}>
     */
    private function resolveSignInTargets(Request $request, array $input): array
    {
        $targets = [['name' => 'signin_ip', 'key' => $this->buildIpKey($request)]];

        $email = $this->resolveInputString($input, 'email');
        if ($email !== null) {
            $targets[] = [
                'name' => 'signin_email',
                'key' => $this->buildEmailKey(strtolower(trim($email))),
            ];
        }

        return $targets;
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return list<array{name: string, key: string}>
     */
    private function resolveTwoFactorTargets(Request $request, array $input): array
    {
        $targets = [['name' => 'twofa_verification_ip', 'key' => $this->buildIpKey($request)]];

        $userId = $this->resolveTwoFactorUserId($input);
        if ($userId !== null) {
            $targets[] = [
                'name' => 'twofa_verification_user',
                'key' => $this->buildUserKey($userId),
            ];
        }

        return $targets;
    }

    /**
     * @param array<string, mixed> $input
     */
    private function resolveTwoFactorUserId(array $input): ?string
    {
        $pendingSessionId = $this->resolveInputString($input, 'pendingSessionId');
        if ($pendingSessionId === null || $this->pendingTwoFactorRepository === null) {
            return null;
        }

        $userId = $this->pendingTwoFactorRepository->findById($pendingSessionId)?->getUserId();

        return is_string($userId) && $userId !== '' ? $userId : null;
    }

    /**
     * @return list<array{name: string, key: string}>
     */
    private function resolveAuthenticatedTargets(Request $request, string $limiterName): array
    {
        $subject = $this->clientIdentityResolver->resolveUserSubject($request);
        if ($subject === null) {
            return [];
        }

        return [['name' => $limiterName, 'key' => $this->buildUserKey($subject)]];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodePayload(string $content): ?array
    {
        try {
            $decoded = $this->serializer->decode(
                $content,
                JsonEncoder::FORMAT,
                [JsonDecode::ASSOCIATIVE => true],
            );
        } catch (NotEncodableValueException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function resolveInput(array $payload): array
    {
        $variables = $payload['variables'] ?? null;
        if (!is_array($variables)) {
            return [];
        }

        $input = $variables['input'] ?? $variables;

        return is_array($input) ? $input : [];
    }

    /**
     * @param array<string, mixed> $input
     */
    private function resolveInputString(array $input, string $key): ?string
    {
        $value = $input[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
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
