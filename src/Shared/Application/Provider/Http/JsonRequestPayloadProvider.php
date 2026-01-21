<?php

declare(strict_types=1);

namespace App\Shared\Application\Provider\Http;

use App\Shared\Application\Decoder\JsonBodyDecoder;

final readonly class JsonRequestPayloadProvider
{
    public function __construct(
        private JsonRequestContentProvider $contentProvider,
        private JsonBodyDecoder $decoder
    ) {
    }

    /**
     * @return array<array|scalar|null>|null
     *
     * @psalm-return array<int|string, array|scalar|null>|null
     */
    public function getPayload(string $invalidJsonMessage): ?array
    {
        return match (true) {
            ($content = $this->contentProvider->content()) === null => null,
            $content === '' => [],
            default => $this->decoder->decodeToArray(
                $content,
                $invalidJsonMessage
            ),
        };
    }
}
