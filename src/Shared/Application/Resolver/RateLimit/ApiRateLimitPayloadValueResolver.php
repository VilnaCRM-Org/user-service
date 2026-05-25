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
    public function __construct(private SerializerInterface $serializer)
    {
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

        $resolved = $this->findStringValue($jsonPayload, $keys);
        if ($resolved !== null) {
            return $resolved;
        }

        return $this->resolveGraphQlQueryValue($jsonPayload, $keys);
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
        if (is_string($query)) {
            return $this->findGraphQlArgumentStringValue($query, $keys);
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
}
