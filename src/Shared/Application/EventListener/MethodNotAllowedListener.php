<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use App\Shared\Application\Provider\Http\AllowedMethodsProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final readonly class MethodNotAllowedListener
{
    private const ERROR_TYPE = '/errors/405';
    private const ERROR_TITLE = 'Method Not Allowed';
    private const ERROR_DETAIL =
        'The requested HTTP method is not allowed on this resource.';

    public function __construct(
        private AllowedMethodsProvider $allowedMethodsProvider
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();
        $allowedMethods = $this->allowedMethodsProvider->getAllowedMethods($path);

        if ($allowedMethods === []) {
            return;
        }

        $requestMethod = $event->getRequest()->getMethod();
        if (!$this->isAllowed($requestMethod, $allowedMethods)) {
            $event->setResponse($this->buildProblemResponse($allowedMethods));
        }
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
