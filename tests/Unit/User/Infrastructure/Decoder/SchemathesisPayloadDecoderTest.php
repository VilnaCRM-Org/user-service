<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Decoder;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\Decoder\SchemathesisPayloadDecoder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

final class SchemathesisPayloadDecoderTest extends UnitTestCase
{
    public function testDecodeReturnsAllKeysForValidPayload(): void
    {
        $serializer = new Serializer([], [new JsonEncoder()]);
        $decoder = new SchemathesisPayloadDecoder($serializer);
        $request = Request::create(
            uri: '/api/users',
            content: json_encode([
                'email' => 'first@example.com',
                'users' => [['email' => 'second@example.com']],
            ], JSON_THROW_ON_ERROR)
        );

        $payload = $decoder->decode($request);

        $this->assertArrayHasKey('email', $payload);
        $this->assertArrayHasKey('users', $payload);
    }
}
