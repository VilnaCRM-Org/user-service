<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final class MethodNotAllowedListener
{
    private const ERROR_TYPE = '/errors/405';
    private const ERROR_TITLE = 'Method Not Allowed';
    private const ERROR_DETAIL =
        'The requested HTTP method is not allowed on this resource.';

    /**
     * @var array<string, array<int, string>>
     */
    private array $restrictions = [
        '/api/users/batch' => ['POST'],
        '/api/users/confirm' => ['PATCH'],
    ];

    public function __invoke(RequestEvent $event): void
    {
        $path = $this->resolveRestrictedPath($event);
        if ($path === null) {
            return;
        }

        $allowed = $this->restrictions[$path];
        if (!$this->isAllowed($event->getRequest()->getMethod(), $allowed)) {
            $event->setResponse($this->buildProblemResponse($allowed));
        }
    }

    private function resolveRestrictedPath(RequestEvent $event): ?string
    {
        if (!$event->isMainRequest()) {
            return null;
        }

        $path = $event->getRequest()->getPathInfo();

        if (!array_key_exists($path, $this->restrictions)) {
            return null;
        }

        return $path;
    }

    /**
     * @param array<int, string> $allowedMethods
     */
    private function isAllowed(string $method, array $allowedMethods): bool
    {
        return \in_array($method, $allowedMethods, true);
    }

    /**
     * @param array<int, string> $allowedMethods
     */
    private function buildProblemResponse(array $allowedMethods): JsonResponse
    {
        return new JsonResponse(
            [
                'type' => self::ERROR_TYPE,
                'title' => self::ERROR_TITLE,
                'detail' => self::ERROR_DETAIL,
                'status' => JsonResponse::HTTP_METHOD_NOT_ALLOWED,
            ],
            JsonResponse::HTTP_METHOD_NOT_ALLOWED,
            [
                'Content-Type' => 'application/problem+json',
                'Allow' => implode(', ', $allowedMethods),
            ]
        );
    }
}
