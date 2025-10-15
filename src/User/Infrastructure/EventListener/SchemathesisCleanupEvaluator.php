<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class SchemathesisCleanupEvaluator
{
    public const HEADER_NAME = 'X-Schemathesis-Test';
    public const HEADER_VALUE = 'cleanup-users';
    private const HANDLED_PATHS = ['/api/users', '/api/users/batch'];

    public function shouldCleanup(Request $request, Response $response): bool
    {
        return !in_array(
            false,
            [
                $this->hasCleanupHeader($request),
                $this->isSuccessfulResponse($response),
                $this->isHandledPath($request),
            ],
            true
        );
    }

    public function isSingleUserPath(Request $request): bool
    {
        return $request->getPathInfo() === self::HANDLED_PATHS[0];
    }

    private function hasCleanupHeader(Request $request): bool
    {
        return $request->headers->get(self::HEADER_NAME) === self::HEADER_VALUE;
    }

    private function isSuccessfulResponse(Response $response): bool
    {
        return in_array(
            $response->getStatusCode(),
            [Response::HTTP_CREATED, Response::HTTP_OK],
            true
        );
    }

    private function isHandledPath(Request $request): bool
    {
        return in_array($request->getPathInfo(), self::HANDLED_PATHS, true);
    }
}
