<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Http;

use function get_object_vars;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;

use function trim;

final readonly class EmptyJsonObjectRequestValidator
{
    public function __construct(private SerializerInterface $serializer)
    {
    }

    public function assertEmptyRequestBody(?Request $request, string $message): void
    {
        if (!$request instanceof Request) {
            return;
        }

        $content = trim($request->getContent());
        if ($content === '') {
            return;
        }

        $this->assertEmptyJsonObject($content, $message);
    }

    private function assertEmptyJsonObject(string $content, string $message): void
    {
        try {
            $isEmptyJsonObject = $this->isEmptyJsonObject($content);
        } catch (NotEncodableValueException) {
            throw new BadRequestHttpException($message);
        }

        if ($isEmptyJsonObject) {
            return;
        }

        throw new BadRequestHttpException($message);
    }

    private function isEmptyJsonObject(string $content): bool
    {
        $decoded = $this->serializer->decode(
            $content,
            JsonEncoder::FORMAT,
            [JsonDecode::ASSOCIATIVE => false],
        );

        return $decoded instanceof \stdClass && get_object_vars($decoded) === [];
    }
}
