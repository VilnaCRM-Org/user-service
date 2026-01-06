<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator\Http;

use App\Shared\Application\Decoder\JsonBodyDecoder;
use App\Shared\Application\Provider\Http\JsonRequestContentProvider;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class JsonRequestValidator
{
    public function __construct(
        private readonly JsonRequestContentProvider $contentProvider,
        private readonly JsonBodyDecoder $decoder
    ) {
    }

    public function assertJsonObjectRequest(
        string $invalidJsonMessage,
        string $expectedObjectMessage
    ): void {
        $content = $this->contentProvider->content();

        if (($content ?? '') === '') {
            return;
        }

        $decoded = $this->decoder->decodeToArray($content, $invalidJsonMessage);

        if (!$this->isNonEmptyList($decoded)) {
            return;
        }

        throw new BadRequestHttpException($expectedObjectMessage);
    }

    /**
     * @param array<int|string, array|bool|float|int|string|null> $decoded
     */
    private function isNonEmptyList(array $decoded): bool
    {
        return $decoded !== [] && \array_is_list($decoded);
    }
}
