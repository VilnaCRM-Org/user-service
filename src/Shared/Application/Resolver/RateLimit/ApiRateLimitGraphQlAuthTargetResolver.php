<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\RateLimit;

use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

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
        private JsonEncoder $jsonEncoder,
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
        if ($signInMutationCount === 0) {
            return [];
        }

        return $this->buildSignInTargets($request, $inspection, $signInMutationCount);
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
            if ($this->countMutations($request, self::SIGNIN_EMAIL_MUTATIONS, null) === 0) {
                return [];
            }

            $email = $this->clientIdentityResolver->resolveTopLevelSignInEmail($request);

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
        $decoded = $this->resolveGraphQlPayload($request);
        $variables = $decoded['variables'] ?? null;

        return is_array($variables) ? $variables : [];
    }

    /**
     * @return array{operationName?: string, query?: string, variables?: array<array-key, array|string|int|float|bool|null>}|null
     */
    private function resolveGraphQlPayload(Request $request): ?array
    {
        $payload = $this->resolveQueryStringPayload($request);
        if ($request->getContentTypeFormat() === 'json') {
            return $this->mergeGraphQlPayload($payload, $this->resolveJsonPayload($request));
        }

        if ($this->isRawGraphQlPost($request)) {
            $content = $request->getContent();

            return $content === ''
                ? $payload
                : $this->mergeGraphQlPayload($payload, ['query' => $content]);
        }

        $operationsPayload = $this->resolveOperationsPayload($request);
        if ($operationsPayload !== null) {
            return $this->mergeGraphQlPayload($payload, $operationsPayload);
        }

        return $payload;
    }

    /**
     * @return array{operationName?: string, query?: string, variables?: array<array-key, array|string|int|float|bool|null>}|null
     */
    private function resolveJsonPayload(Request $request): ?array
    {
        try {
            $payload = $request->toArray();
            /** @var array<array-key, array|string|int|float|bool|null> $payload */
            return $this->normalizeGraphQlPayload($payload);
        } catch (JsonException) {
            return null;
        }
    }

    /**
     * @return array{operationName?: string, query?: string, variables?: array<array-key, array|string|int|float|bool|null>}|null
     */
    private function resolveOperationsPayload(Request $request): ?array
    {
        $operations = $this->resolveOperationsParameter($request);
        if ($operations === null) {
            return null;
        }

        try {
            $payload = $this->jsonEncoder->decode($operations, JsonEncoder::FORMAT);
        } catch (NotEncodableValueException) {
            return null;
        }

        /** @var array<array-key, array|string|int|float|bool|null> $payload */
        return is_array($payload) ? $this->normalizeGraphQlPayload($payload) : null;
    }

    private function resolveOperationsParameter(Request $request): ?string
    {
        if (!in_array($request->getContentTypeFormat(), ['form', 'multipart'], true)) {
            return null;
        }

        $requestParameters = $request->request->all();
        $operations = $requestParameters['operations'] ?? null;

        return is_string($operations) ? $operations : null;
    }

    /**
     * @return array{operationName?: string, query?: string, variables?: array<array-key, array|string|int|float|bool|null>}|null
     */
    private function resolveQueryStringPayload(Request $request): ?array
    {
        $payload = $request->query->all();
        /** @var array<array-key, array|string|int|float|bool|null> $payload */
        return $this->normalizeGraphQlPayload($payload);
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null> $payload
     *
     * @return array{operationName?: string, query?: string, variables?: array<array-key, array|string|int|float|bool|null>}|null
     */
    private function normalizeGraphQlPayload(array $payload): ?array
    {
        $normalized = [];
        foreach (['operationName', 'query'] as $fieldName) {
            $value = $payload[$fieldName] ?? null;
            if (is_string($value)) {
                $normalized[$fieldName] = $value;
            }
        }

        $variables = $payload['variables'] ?? null;
        $normalizedVariables = $this->normalizeVariables($variables);
        if ($normalizedVariables !== null) {
            $normalized['variables'] = $normalizedVariables;
        }

        return $normalized === [] ? null : $normalized;
    }

    /**
     * @param array{operationName?: string, query?: string, variables?: array<array-key, array|string|int|float|bool|null>}|null $basePayload
     * @param array{operationName?: string, query?: string, variables?: array<array-key, array|string|int|float|bool|null>}|null $overlayPayload
     *
     * @return array{operationName?: string, query?: string, variables?: array<array-key, array|string|int|float|bool|null>}|null
     */
    private function mergeGraphQlPayload(?array $basePayload, ?array $overlayPayload): ?array
    {
        if ($basePayload === null) {
            return $overlayPayload;
        }

        if ($overlayPayload === null) {
            return $basePayload;
        }

        $mergedPayload = $basePayload;
        foreach (['operationName', 'query'] as $fieldName) {
            if (array_key_exists($fieldName, $overlayPayload)) {
                $mergedPayload[$fieldName] = $overlayPayload[$fieldName];
            }
        }

        if (array_key_exists('variables', $overlayPayload)) {
            $mergedPayload['variables'] = $overlayPayload['variables'];
        }

        return $mergedPayload;
    }

    /**
     * @return array<array-key, array|string|int|float|bool|null>|null
     */
    private function normalizeVariables(mixed $variables): ?array
    {
        if (is_string($variables)) {
            try {
                $variables = $this->jsonEncoder->decode($variables, JsonEncoder::FORMAT);
            } catch (NotEncodableValueException) {
                return null;
            }
        }

        if (!is_array($variables)) {
            return null;
        }

        /** @var array<array-key, array|string|int|float|bool|null> $variables */
        return $this->normalizeVariableArray($variables);
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null> $variables
     *
     * @return array<array-key, array|string|int|float|bool|null>
     */
    private function normalizeVariableArray(array $variables): array
    {
        $normalized = [];
        foreach ($variables as $key => $value) {
            if (is_array($value)) {
                $normalized[$key] = $this->normalizeVariableArray($value);
                continue;
            }

            if (is_string($value) || is_int($value) || is_float($value) || is_bool($value) || $value === null) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    private function isRawGraphQlPost(Request $request): bool
    {
        if ($request->getContentTypeFormat() === 'graphql') {
            return true;
        }

        $contentType = $request->headers->get('CONTENT_TYPE', '');
        $mimeType = explode(';', strtolower($contentType))[0];

        return $mimeType === 'application/graphql';
    }

    private function resolveGraphQlQueryInspection(
        Request $request
    ): ?ApiRateLimitGraphQlQueryInspection {
        $payload = $request->getContent();
        $decoded = $this->resolveGraphQlPayload($request);
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
