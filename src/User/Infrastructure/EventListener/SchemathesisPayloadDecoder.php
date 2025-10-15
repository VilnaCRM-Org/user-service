<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventListener;

use JsonException;
use Symfony\Component\HttpFoundation\Request;

final class SchemathesisPayloadDecoder
{
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
            return json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
    }
}
