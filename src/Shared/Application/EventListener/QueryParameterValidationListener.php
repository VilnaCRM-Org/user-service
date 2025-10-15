<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use App\Shared\Application\EventListener\QueryParameter\QueryParameterRule;
use App\Shared\Application\EventListener\QueryParameter\QueryParameterViolation;
use App\Shared\Application\EventListener\QueryParameter\QueryViolationFinder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final class QueryParameterValidationListener
{
    /**
     * @param iterable<QueryParameterRule> $rules
     */
    public function __construct(
        private readonly iterable $rules,
        private readonly QueryViolationFinder $violationFinder
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if ($event->isMainRequest()) {
            $this->setViolationResponse($event);
        }
    }

    private function setViolationResponse(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        $violation = $this->violationFinder->find(
            $this->rules,
            $path,
            $request->query->all()
        );

        if ($violation === null) {
            return;
        }

        $event->setResponse($this->buildProblemResponse($violation));
    }

    private function buildProblemResponse(
        QueryParameterViolation $violation
    ): JsonResponse {
        return new JsonResponse(
            [
                'type' => '/errors/400',
                'title' => $violation->title,
                'detail' => $violation->detail,
                'status' => 400,
            ],
            400,
            [
                'Content-Type' => 'application/problem+json',
            ]
        );
    }
}
