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
     * Reads a string value from a nested JSON payload path (e.g. GraphQL
     * `variables.input.pendingSessionId`). Returns null when the path or value
     * is missing.
     *
     * @param list<string> $path
     * @param list<string> $keys
     */
    public function resolveNested(Request $request, array $path, array $keys): ?string
    {
        $rawPayload = trim($request->getContent());

        try {
            $payload = $this->serializer->decode(
                $rawPayload,
                JsonEncoder::FORMAT,
                [JsonDecode::ASSOCIATIVE => true],
            );
        } catch (NotEncodableValueException) {
            return null;
        }

        foreach ($path as $segment) {
            if (!is_array($payload) || !is_array($payload[$segment] ?? null)) {
                return null;
            }

            $payload = $payload[$segment];
        }

        return is_array($payload) ? $this->findStringValue($payload, $keys) : null;
    }

    /**
     * @param list<string> $keys
     */
    private function resolveJsonPayloadValue(string $rawPayload, array $keys): ?string
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

        if (!is_array($jsonPayload)) {
            return null;
        }

        return $this->findStringValue($jsonPayload, $keys);
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
     * @param array<string, array<int, string>|bool|float|int|string|null> $payload
     * @param list<string> $keys
     */
    private function findStringValue(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $payload[$key] ?? null;
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
