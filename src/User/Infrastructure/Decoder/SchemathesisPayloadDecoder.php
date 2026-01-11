<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Decoder;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class SchemathesisPayloadDecoder
{
    public function __construct(
        private SerializerInterface $serializer
    ) {
    }

    /**
     * @return array{email?: string|null, users?: array<int, array{email?: string|null}|scalar|null>}
     */
    public function decode(Request $request): array
    {
        return $this->decodeContent($request->getContent());
    }

    /**
     * @return array{email?: string|null, users?: array<int, array{email?: string|null}|scalar|null>}
     */
    private function decodeContent(string $content): array
    {
        $payload = $this->tryDecode($content);

        return is_array($payload) ? $payload : [];
    }

    private function tryDecode(string $content): mixed
    {
        try {
            return $this->serializer->decode($content, JsonEncoder::FORMAT);
        } catch (NotEncodableValueException) {
            return null;
        }
    }
}
