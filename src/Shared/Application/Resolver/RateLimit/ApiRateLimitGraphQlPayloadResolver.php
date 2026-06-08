<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\RateLimit;

use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

final readonly class ApiRateLimitGraphQlPayloadResolver
{
    public function __construct(
        private JsonEncoder $jsonEncoder,
    ) {
    }

    /**
     * @return array{operationName?: string, query?: string, variables?: array<array-key, array|string|int|float|bool|null>}|null
     */
    public function resolve(Request $request): ?array
    {
        $payload = $this->resolveQueryStringPayload($request);
        if ($request->getContentTypeFormat() === 'json') {
            return $this->mergeGraphQlPayload($payload, $this->resolveJsonPayload($request));
        }

        if ($this->isRawGraphQlPost($request)) {
            return $this->resolveRawGraphQlPayload($request, $payload);
        }

        $operationsPayload = $this->resolveOperationsPayload($request);
        if ($operationsPayload !== null) {
            return $this->mergeGraphQlPayload($payload, $operationsPayload);
        }

        return $payload;
    }

    /**
     * @return array<array-key, array|string|int|float|bool|null>
     */
    public function resolveVariables(Request $request): array
    {
        $decoded = $this->resolve($request);
        $variables = $decoded['variables'] ?? null;

        return is_array($variables) ? $variables : [];
    }

    /**
     * @param array{operationName?: string, query?: string, variables?: array<array-key, array|string|int|float|bool|null>}|null $payload
     *
     * @return array{operationName?: string, query?: string, variables?: array<array-key, array|string|int|float|bool|null>}|null
     */
    private function resolveRawGraphQlPayload(Request $request, ?array $payload): ?array
    {
        $content = $request->getContent();
        if ($content !== '') {
            return $this->mergeGraphQlPayload($payload, ['query' => $content]);
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

        if (!is_array($payload)) {
            return null;
        }

        /** @var array<array-key, array|string|int|float|bool|null> $normalizedPayload */
        $normalizedPayload = $payload;

        return $this->normalizeGraphQlPayload($normalizedPayload);
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
        $mergedPayload = $basePayload ?? [];
        if ($overlayPayload === null) {
            return $mergedPayload === [] ? null : $mergedPayload;
        }

        if (array_key_exists('operationName', $overlayPayload)) {
            $mergedPayload['operationName'] = $overlayPayload['operationName'];
        }

        if (array_key_exists('query', $overlayPayload)) {
            $mergedPayload['query'] = $overlayPayload['query'];
        }

        if (array_key_exists('variables', $overlayPayload)) {
            $mergedPayload['variables'] = $overlayPayload['variables'];
        }

        return $mergedPayload === [] ? null : $mergedPayload;
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
                $variables = null;
            }
        }

        if (!is_array($variables)) {
            return null;
        }

        /** @var array<array-key, array|string|int|float|bool|null> $normalizedVariables */
        $normalizedVariables = $variables;

        return $this->normalizeVariableArray($normalizedVariables);
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
            $normalized[$key] = is_array($value) ? $this->normalizeVariableArray($value) : $value;
        }

        return $normalized;
    }

    private function isRawGraphQlPost(Request $request): bool
    {
        $contentType = $request->headers->get('CONTENT_TYPE', '');
        $mimeType = explode(';', strtolower($contentType))[0];

        return $request->getContentTypeFormat() === 'graphql'
            || $mimeType === 'application/graphql';
    }
}
