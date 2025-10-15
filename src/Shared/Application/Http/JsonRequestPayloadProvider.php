<?php

declare(strict_types=1);

namespace App\Shared\Application\Http;

final readonly class JsonRequestPayloadProvider
{
    public function __construct(
        private JsonRequestContentProvider $contentProvider,
        private JsonBodyDecoder $decoder
    ) {
    }

    /**
     * @return array<string, array|string|int|float|bool|null>|null
     */
    public function getPayload(string $invalidJsonMessage): ?array
    {
        $content = null;

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
