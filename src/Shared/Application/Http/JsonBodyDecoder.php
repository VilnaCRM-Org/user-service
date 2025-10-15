<?php

declare(strict_types=1);

namespace App\Shared\Application\Http;

use JsonException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class JsonBodyDecoder
{
    /**
     * @return array<int|string, array|scalar|null>
     */
    public function decodeToArray(string $content, string $errorMessage): array
    {
        return $this->decodeContent($content, $errorMessage);
    }

    /**
     * @return array<int|string, array|scalar|null>
     */
    private function decodeContent(string $content, string $errorMessage): array
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
            return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new BadRequestHttpException($errorMessage, $exception);
        }
    }
}
