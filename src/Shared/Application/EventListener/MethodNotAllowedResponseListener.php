<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

final class MethodNotAllowedResponseListener
{
    private const PROBLEM_JSON_STATUS_CODES = [
        Response::HTTP_METHOD_NOT_ALLOWED,
        Response::HTTP_REQUEST_ENTITY_TOO_LARGE,
    ];

    public function __invoke(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        if (!in_array($response->getStatusCode(), self::PROBLEM_JSON_STATUS_CODES, true)) {
            return;
        }

        if (!str_contains($response->headers->get('Content-Type', ''), 'application/json')) {
            return;
        }

        $response->headers->set('Content-Type', 'application/problem+json');
    }
}
