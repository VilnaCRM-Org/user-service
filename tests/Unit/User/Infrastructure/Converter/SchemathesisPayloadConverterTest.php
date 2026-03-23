<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Converter;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\Converter\SchemathesisPayloadConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

final class SchemathesisPayloadConverterTest extends UnitTestCase
{
    public function testDecodeReturnsAllKeysForValidPayload(): void
    {
        $serializer = new Serializer([], [new JsonEncoder()]);
        $converter = new SchemathesisPayloadConverter($serializer);
        $request = Request::create(
            uri: '/api/users',
            content: json_encode([
                'email' => 'first@example.com',
                'users' => [['email' => 'second@example.com']],
            ], JSON_THROW_ON_ERROR)
        );

        $payload = $converter->decode($request);

        $this->assertArrayHasKey('email', $payload);
        $this->assertArrayHasKey('users', $payload);
    }
}
