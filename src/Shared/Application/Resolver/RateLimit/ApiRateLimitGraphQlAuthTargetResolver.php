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
        'passkeyRegistrationOptionsUser',
        'passkeyRegistrationCompleteUser',
    ];
    private const SIGNIN_MUTATIONS = [
        'signInUser',
        'passkeySignInOptionsUser',
        'passkeySignInCompleteUser',
    ];
    private const SIGNIN_EMAIL_MUTATIONS = [
        'signInUser',
        'passkeySignInOptionsUser',
    ];

    public function __construct(
        private ApiRateLimitClientIdentityResolver $clientIdentityResolver,
        private ApiRateLimitGraphQlQueryInspector $graphQlQueryInspector,
        private ApiRateLimitGraphQlPayloadResolver $graphQlPayloadResolver,
    ) {
    }

    /**
     * @return list<array{name: 'registration'|'signin_email'|'signin_ip', key: string}>
     */
    public function resolve(Request $request): array
    {
        if (!$this->supports($request)) {
            return [];
        }

        $inspection = $this->resolveGraphQlQueryInspection($request);
        return [
            ...$this->buildRegistrationTargets($request, $inspection),
            ...$this->buildSignInMutationTargets($request, $inspection),
        ];
    }

    /**
     * @return list<array{name: 'registration', key: string}>
     */
    private function buildRegistrationTargets(
        Request $request,
        ?ApiRateLimitGraphQlQueryInspection $inspection
    ): array {
        $targets = [];
        $registrationMutationCount = $this->countMutations(
            $request,
            self::REGISTRATION_MUTATIONS,
            $inspection
        );
        for ($index = 0; $index < $registrationMutationCount; ++$index) {
            $targets[] = ['name' => 'registration', 'key' => $this->buildIpKey($request)];
        }

        return $targets;
    }

    /**
     * @return list<array{name: 'signin_email'|'signin_ip', key: string}>
     */
    private function buildSignInMutationTargets(
        Request $request,
        ?ApiRateLimitGraphQlQueryInspection $inspection
    ): array {
        $signInMutationCount = $this->countMutations($request, self::SIGNIN_MUTATIONS, $inspection);
        return $signInMutationCount === 0
            ? []
            : $this->buildSignInTargets($request, $inspection, $signInMutationCount);
    }

    private function supports(Request $request): bool
    {
        return strtoupper($request->getMethod()) === 'POST'
            && $request->getPathInfo() === self::GRAPHQL_PATH;
    }

    /**
     * @return list<array{name: 'signin_email'|'signin_ip', key: string}>
     */
    private function buildSignInTargets(
        Request $request,
        ?ApiRateLimitGraphQlQueryInspection $inspection,
        int $signInMutationCount
    ): array {
        $targets = [];
        for ($index = 0; $index < $signInMutationCount; ++$index) {
            $targets[] = ['name' => 'signin_ip', 'key' => $this->buildIpKey($request)];
        }

        foreach ($this->resolveSignInEmails($request, $inspection) as $email) {
            $targets[] = ['name' => 'signin_email', 'key' => $this->buildEmailKey($email)];
        }

        return $targets;
    }

    /**
     * @return list<string>
     */
    private function resolveSignInEmails(
        Request $request,
        ?ApiRateLimitGraphQlQueryInspection $inspection
    ): array {
        if ($inspection === null) {
            $email = $this->countMutations($request, self::SIGNIN_EMAIL_MUTATIONS, null) === 0
                ? null
                : $this->clientIdentityResolver->resolveTopLevelSignInEmail($request);

            return $email === null ? [] : [$email];
        }

        return $this->normalizeEmails($inspection->findTopLevelInputStringValuesForMutationFields(
            $this->resolveGraphQlVariables($request),
            self::SIGNIN_EMAIL_MUTATIONS,
            ['email'],
        ));
    }

    /**
     * @param list<string> $mutationNames
     */
    private function countMutations(
        Request $request,
        array $mutationNames,
        ?ApiRateLimitGraphQlQueryInspection $inspection
    ): int {
        if ($inspection !== null) {
            return $inspection->countMutationFields($mutationNames);
        }

        $payload = $request->getContent();
        foreach ($mutationNames as $mutationName) {
            if (preg_match('/\b' . $mutationName . '\b/', $payload) === 1) {
                return 1;
            }
        }

        return 0;
    }

    /**
     * @return array<array-key, array|string|int|float|bool|null>
     */
    private function resolveGraphQlVariables(Request $request): array
    {
        return $this->graphQlPayloadResolver->resolveVariables($request);
    }

    private function resolveGraphQlQueryInspection(
        Request $request
    ): ?ApiRateLimitGraphQlQueryInspection {
        $payload = $request->getContent();
        $decoded = $this->graphQlPayloadResolver->resolve($request);
        if ($decoded === null) {
            return $this->graphQlQueryInspector->inspect($payload, null);
        }

        $query = $decoded['query'] ?? null;
        if (!is_string($query)) {
            return null;
        }

        $operationName = $decoded['operationName'] ?? null;
        return $this->graphQlQueryInspector->inspect(
            $query,
            is_string($operationName) ? $operationName : null
        );
    }

    private function buildIpKey(Request $request): string
    {
        $ipAddress = $request->getClientIp() ?? '0.0.0.0';
        return sprintf('ip:%s', $ipAddress);
    }

    private function buildEmailKey(string $email): string
    {
        return sprintf('email:%s', strtolower(trim($email)));
    }

    /**
     * @param list<string> $emails
     *
     * @return list<string>
     */
    private function normalizeEmails(array $emails): array
    {
        $normalizedEmails = [];
        foreach ($emails as $email) {
            if (trim($email) !== '') {
                $normalizedEmails[] = $email;
            }
        }

        return $normalizedEmails;
    }
}
