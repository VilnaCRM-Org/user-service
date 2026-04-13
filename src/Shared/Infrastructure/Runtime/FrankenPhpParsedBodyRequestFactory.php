<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime;

use Symfony\Component\HttpFoundation\Request;

final class FrankenPhpParsedBodyRequestFactory
{
    public function create(Request $request): Request
    {
        try {
            [$post, $files] = request_parse_body();
        } catch (\RequestParseBodyException) {
            return $this->createFallbackRequest($request);
        }

        return $request->duplicate(null, $post, null, null, $files);
    }

    private function createFallbackRequest(Request $request): Request
    {
        return $request->duplicate(
            null,
            $request->request->all(),
            null,
            null,
            $request->files->all(),
        );
    }
}
