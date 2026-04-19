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
        if ($this->isEmptyJsonObject($content, $message)) {
            return;
        }

        throw new BadRequestHttpException($message);
    }

    private function isEmptyJsonObject(string $content, string $message): bool
    {
        try {
            $decoded = $this->serializer->decode(
                $content,
                JsonEncoder::FORMAT,
                [JsonDecode::ASSOCIATIVE => false],
            );
        } catch (NotEncodableValueException) {
            throw new BadRequestHttpException($message);
        }

        return $decoded instanceof \stdClass && get_object_vars($decoded) === [];
    }
}
