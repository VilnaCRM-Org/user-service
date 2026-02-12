<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Rejects GraphQL batch requests (JSON arrays) to prevent rate limit bypass.
 *
 * AC: NFR-59 - GraphQL batch requests must be rejected with 400 Bad Request
 * Security: RC-01 (TEA R3) - GraphQL batching bypasses ALL rate limiting
 * Reference: OWASP API2:2023 Broken Authentication
 *
 * Priority: 130 (before rate limiter at 120 and security firewall)
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 130)]
final readonly class GraphQLBatchRejectListener
{
    private const GRAPHQL_PATH = '/api/graphql';

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Only apply to GraphQL endpoint
        if ($request->getPathInfo() !== self::GRAPHQL_PATH) {
            return;
        }

        // Only apply to POST requests with JSON content
        if ($request->getMethod() !== 'POST') {
            return;
        }

        $content = $request->getContent();
        if ($content === '') {
            return;
        }

        // Check if the request body is a JSON array (batch request)
        try {
            $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            // Invalid JSON - let the normal error handling deal with it
            return;
        }

        // If the decoded content is an array with numeric keys (JSON array), reject it
        if (\is_array($decoded) && array_is_list($decoded)) {
            $event->setResponse(new JsonResponse(
                [
                    'type' => 'about:blank',
                    'title' => 'Bad Request',
                    'status' => Response::HTTP_BAD_REQUEST,
                    'detail' => 'GraphQL batch requests (JSON arrays) are not allowed. ' .
                               'Send individual requests instead to prevent rate limit bypass (OWASP API2:2023).',
                ],
                Response::HTTP_BAD_REQUEST
            ));
        }
    }
}
