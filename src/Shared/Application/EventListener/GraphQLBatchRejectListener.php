<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Rejects GraphQL batch requests (JSON arrays) to prevent rate limit bypass.
 *
 * AC: NFR-59 - GraphQL batch requests must be rejected with 400 Bad Request
 * Security: RC-01 (TEA R3) - GraphQL batching bypasses ALL rate limiting
 * Reference: OWASP API2:2023 Broken Authentication
 *
 * Priority: 130 (before rate limiter at 120 and security firewall)
 */
final readonly class GraphQLBatchRejectListener
{
    private const GRAPHQL_PATH = '/api/graphql';
    private const BATCH_REJECT_DETAIL =
        'GraphQL batch requests are not allowed. Use individual requests (OWASP API2:2023).';

    public function __construct(
        private SerializerInterface $serializer,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$this->isGraphQlPost($event->getRequest())) {
            return;
        }

        if (!$this->isBatchRequest($event->getRequest()->getContent())) {
            return;
        }

        $event->setResponse(new JsonResponse(
            [
                'type' => 'about:blank',
                'title' => 'Bad Request',
                'status' => Response::HTTP_BAD_REQUEST,
                'detail' => self::BATCH_REJECT_DETAIL,
            ],
            Response::HTTP_BAD_REQUEST
        ));
    }

    private function isGraphQlPost(Request $request): bool
    {
        return $request->getPathInfo() === self::GRAPHQL_PATH
            && $request->getMethod() === 'POST';
    }

    private function isBatchRequest(string $content): bool
    {
        try {
            $decoded = $this->serializer->decode(
                $content,
                JsonEncoder::FORMAT,
                [JsonDecode::ASSOCIATIVE => true],
            );
        } catch (NotEncodableValueException) {
            return false;
        }

        return \is_array($decoded) && array_is_list($decoded);
    }
}
