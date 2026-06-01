<?php

declare(strict_types=1);

namespace App\User\Application\EventListener;

use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;

final readonly class PasskeyGraphQlRequestResolver
{
    public function __construct(
        private JsonEncoder $jsonEncoder,
    ) {
    }

    public function resolveQuery(Request $request): ?string
    {
        if ($request->getMethod() !== Request::METHOD_POST) {
            return $this->resolveQueryParameter($request);
        }

        $payloadQuery = $this->resolvePayloadQuery($request);
        if ($payloadQuery !== null) {
            return $payloadQuery;
        }

        if ($this->isRawGraphQlPost($request)) {
            $content = $request->getContent();

            return $content !== '' ? $content : null;
        }

        return $this->resolveQueryStringQuery($request);
    }

    public function resolveOperationName(Request $request): ?string
    {
        if ($request->getMethod() !== Request::METHOD_POST) {
            return $this->resolveQueryStringOperationName($request);
        }

        $payload = $this->resolvePayload($request);

        return $this->normalizeStringValue(
            $payload['operationName'] ?? $request->query->get('operationName')
        );
    }

    private function resolvePayloadQuery(Request $request): ?string
    {
        $payload = $this->resolvePayload($request);
        $query = $payload['query'] ?? null;

        return is_string($query) && trim($query) !== '' ? $query : null;
    }

    private function resolveQueryStringQuery(Request $request): ?string
    {
        $query = $this->resolveQueryParameter($request);
        if ($query !== null) {
            return $query;
        }

        $content = $request->getContent();

        return $content !== '' ? $content : null;
    }

    private function resolveQueryParameter(Request $request): ?string
    {
        return $this->normalizeStringValue($request->query->get('query'));
    }

    private function resolveQueryStringOperationName(Request $request): ?string
    {
        return $this->normalizeStringValue($request->query->get('operationName'));
    }

    private function normalizeStringValue(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
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

    /**
     * @return array{operationName?: string, query?: string}|null
     */
    private function resolvePayload(Request $request): ?array
    {
        $operationsPayload = $this->resolveOperationsPayload($request);
        if ($operationsPayload !== null) {
            return $operationsPayload;
        }

        try {
            return $this->normalizePayload($request->toArray());
        } catch (JsonException) {
            return null;
        }
    }

    /**
     * @return array{operationName?: string, query?: string}|null
     */
    private function resolveOperationsPayload(Request $request): ?array
    {
        $operations = $this->resolveOperationsParameter($request);
        if ($operations === null) {
            return null;
        }

        return $this->decodeOperationsPayload($operations);
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
     * @return array{operationName?: string, query?: string}|null
     */
    private function decodeOperationsPayload(string $operations): ?array
    {
        try {
            $payload = $this->jsonEncoder->decode(
                $operations,
                JsonEncoder::FORMAT
            );
        } catch (NotEncodableValueException) {
            return null;
        }

        return is_array($payload) ? $this->normalizePayload($payload) : null;
    }

    /**
     * @param array<array-key, array|scalar|null> $payload
     *
     * @return array{operationName?: string, query?: string}|null
     */
    private function normalizePayload(array $payload): ?array
    {
        $normalized = [];
        foreach (['operationName', 'query'] as $fieldName) {
            $value = $payload[$fieldName] ?? null;
            if (is_string($value)) {
                $normalized[$fieldName] = $value;
            }
        }

        return $normalized === [] ? null : $normalized;
    }
}
