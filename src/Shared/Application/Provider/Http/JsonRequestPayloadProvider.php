<?php

declare(strict_types=1);

namespace App\Shared\Application\Provider\Http;

use App\Shared\Application\Converter\JsonBodyConverter;

final readonly class JsonRequestPayloadProvider
{
    public function __construct(
        private JsonRequestContentProvider $contentProvider,
        private JsonBodyConverter $jsonBodyConverter
    ) {
    }

    /**
     * @return array<array|scalar|null>|null
     *
     * @psalm-return array<int|string, array|scalar|null>|null
     */
    public function getPayload(string $invalidJsonMessage): ?array
    {
        $content = $this->contentProvider->content();

        if ($content === null) {
            return null;
        }

        if ($content === '') {
            return [];
        }

        return $this->jsonBodyConverter->decodeToArray($content, $invalidJsonMessage);
    }
}
