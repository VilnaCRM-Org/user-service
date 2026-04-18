<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime\Factory;

use Symfony\Component\HttpFoundation\Request;

final class FrankenPhpLegacyBodyRequestFactory
{
    public function create(Request $request): Request
    {
        $content = null;
        $post = $request->request->all();

        if ($this->shouldParseBody($request)) {
            $rawContent = file_get_contents('php://input');
            $content = \is_string($rawContent) ? $rawContent : null;
            if ($content !== null && $content !== '') {
                $parsedPost = [];
                parse_str($content, $parsedPost);
                $post = $parsedPost;
            }
        }

        return new Request(
            $request->query->all(),
            $post,
            [],
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $content,
        );
    }

    private function shouldParseBody(Request $request): bool
    {
        $contentType = $request->headers->get('CONTENT_TYPE', '');

        return $contentType === '' || str_starts_with(
            $contentType,
            'application/x-www-form-urlencoded',
        );
    }
}
