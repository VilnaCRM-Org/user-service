<?php

declare(strict_types=1);

namespace App\Shared\Application\Decoder;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class JsonBodyDecoder
{
    public function __construct(
        private SerializerInterface $serializer
    ) {
    }

    /**
     * @return array<int|string, array|scalar|null>
     */
    public function decodeToArray(string $content, string $errorMessage): array
    {
        $decoded = $this->tryDecode($content, $errorMessage);

        if (!is_array($decoded)) {
            throw new BadRequestHttpException($errorMessage);
        }

        return $decoded;
    }

    private function tryDecode(string $content, string $errorMessage): mixed
    {
        try {
            return $this->serializer->decode($content, JsonEncoder::FORMAT);
        } catch (NotEncodableValueException $exception) {
            throw new BadRequestHttpException($errorMessage, $exception);
        }
    }
}
