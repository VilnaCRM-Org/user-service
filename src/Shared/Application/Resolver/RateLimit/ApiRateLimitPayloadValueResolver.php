<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver\RateLimit;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class ApiRateLimitPayloadValueResolver
{
    public function __construct(
        private SerializerInterface $serializer,
        private ApiRateLimitGraphQlQueryInspector $graphQlQueryInspector,
    ) {
    }

    /**
     * @param list<string> $keys
     */
    public function resolve(Request $request, array $keys): ?string
    {
        $rawPayload = trim($request->getContent());
        $jsonValue = $this->resolveJsonPayloadValue($rawPayload, $keys);
        if ($jsonValue !== null) {
            return $jsonValue;
        }

        return $this->resolveFormPayloadValue($rawPayload, $keys);
    }

    /**
     * @param list<string> $keys
     */
    private function resolveJsonPayloadValue(string $rawPayload, array $keys): ?string
    {
        $jsonPayload = $this->decodeJsonPayload($rawPayload);
        if ($jsonPayload === null) {
            return null;
        }

        $graphQlValue = $this->resolveGraphQlQueryValue($jsonPayload, $keys);
        if ($graphQlValue !== null) {
            return $graphQlValue;
        }

        return $this->findStringValue($jsonPayload, $keys);
    }

    /**
     * @return array<array-key, array|string|int|float|bool|null>|null
     */
    private function decodeJsonPayload(string $rawPayload): ?array
    {
        try {
            $jsonPayload = $this->serializer->decode(
                $rawPayload,
                JsonEncoder::FORMAT,
                [JsonDecode::ASSOCIATIVE => true],
            );
        } catch (NotEncodableValueException) {
            return null;
        }

        return is_array($jsonPayload) ? $jsonPayload : null;
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null> $jsonPayload
     * @param list<string> $keys
     */
    private function resolveGraphQlQueryValue(array $jsonPayload, array $keys): ?string
    {
        $query = $jsonPayload['query'] ?? null;
        if (!is_string($query)) {
            return null;
        }
        $operationName = $jsonPayload['operationName'] ?? null;
        $inspection = $this->graphQlQueryInspector->inspect(
            $query,
            is_string($operationName) ? $operationName : null
        );

        $variables = $jsonPayload['variables'] ?? null;
        return $inspection !== null
            ? $this->resolveGraphQlInspectionValue($inspection, $variables, $keys)
            : $this->resolveLegacyGraphQlQueryValue($query, $variables, $keys);
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null>|string|int|float|bool|null $variables
     * @param list<string> $keys
     */
    private function resolveGraphQlInspectionValue(
        ApiRateLimitGraphQlQueryInspection $inspection,
        array|string|int|float|bool|null $variables,
        array $keys
    ): ?string {
        $inlineValue = $inspection->findArgumentStringValue($keys);
        if ($inlineValue !== null) {
            return $inlineValue;
        }

        if (!is_array($variables)) {
            return null;
        }

        $argumentValue = $inspection->findArgumentVariableValue($variables, $keys);
        if ($argumentValue !== null) {
            return $argumentValue;
        }

        return $inspection->findInputObjectVariableValue($variables, $keys);
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null>|string|int|float|bool|null $variables
     * @param list<string> $keys
     */
    private function resolveLegacyGraphQlQueryValue(
        string $query,
        array|string|int|float|bool|null $variables,
        array $keys
    ): ?string {
        $inlineValue = $this->findGraphQlArgumentStringValue($query, $keys);
        if ($inlineValue !== null) {
            return $inlineValue;
        }

        if (!is_array($variables)) {
            return null;
        }

        $argumentValue = $this->findGraphQlArgumentVariableValue($query, $variables, $keys);
        if ($argumentValue !== null) {
            return $argumentValue;
        }

        return null;
    }

    /**
     * @param list<string> $keys
     */
    private function resolveFormPayloadValue(string $rawPayload, array $keys): ?string
    {
        parse_str($rawPayload, $formPayload);

        return $this->findStringValue($formPayload, $keys);
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null> $payload
     * @param list<string> $keys
     */
    private function findStringValue(array $payload, array $keys): ?string
    {
        foreach ($payload as $key => $value) {
            $resolved = $this->resolvePayloadEntry($key, $value, $keys);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null>|string|int|float|bool|null $value
     * @param list<string> $keys
     */
    private function resolvePayloadEntry(
        int|string $key,
        array|string|int|float|bool|null $value,
        array $keys
    ): ?string {
        if (is_string($key) && $this->isMatchingStringValue($key, $value, $keys)) {
            return $value;
        }

        return is_array($value) ? $this->findStringValue($value, $keys) : null;
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null>|string|int|float|bool|null $value
     * @param list<string> $keys
     */
    private function isMatchingStringValue(
        string $key,
        array|string|int|float|bool|null $value,
        array $keys
    ): bool {
        return in_array($key, $keys, true) && is_string($value) && $value !== '';
    }

    /**
     * @param list<string> $keys
     */
    private function findGraphQlArgumentStringValue(string $query, array $keys): ?string
    {
        foreach ($keys as $key) {
            $pattern = '/\b' . preg_quote($key, '/') . '\s*:\s*"([^"]+)"/';
            if (preg_match($pattern, $query, $matches) === 1) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * @param array<array-key, array|string|int|float|bool|null> $variables
     * @param list<string> $keys
     */
    private function findGraphQlArgumentVariableValue(
        string $query,
        array $variables,
        array $keys
    ): ?string {
        foreach ($keys as $key) {
            $pattern = '/\b' . preg_quote($key, '/') . '\s*:\s*\$([A-Za-z_]\w*)\b/';
            if (preg_match($pattern, $query, $matches) !== 1) {
                continue;
            }

            $value = $variables[$matches[1]] ?? null;
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
