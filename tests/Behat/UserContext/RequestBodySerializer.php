<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Behat\UserContext\Input\RequestInput;
use Symfony\Component\Serializer\SerializerInterface;

final class RequestBodySerializer
{
    private const EMPTY_BODY_BY_METHOD = [
        'POST' => '{}',
        'PUT' => '{}',
        'PATCH' => '{}',
    ];

    public function __construct(
        private readonly SerializerInterface $serializer
    ) {
    }

    public function serialize(?RequestInput $requestBody, string $method): string
    {
        $defaultPayload = self::EMPTY_BODY_BY_METHOD[$method] ?? '';

        if ($requestBody === null) {
            return $defaultPayload;
        }

        if (!$requestBody instanceof RequestInput) {
            return $this->serializer->serialize($requestBody, 'json');
        }

        return $this->serializeRequestInput($requestBody);
    }

    private function serializeRequestInput(RequestInput $requestBody): string
    {
        $payload = array_filter(
            get_object_vars($requestBody),
            static fn ($value): bool => self::isNonEmptyValue($value)
        );

        return json_encode((object) $payload, JSON_THROW_ON_ERROR);
    }

    private static function isNonEmptyValue(mixed $value): bool
    {
        return $value !== null && (!is_string($value) || $value !== '');
    }
}
