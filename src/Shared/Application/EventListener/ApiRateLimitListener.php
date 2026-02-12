<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use LogicException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/** @infection-ignore-all */
final readonly class ApiRateLimitListener
{
    /**
     * @param array<string, RateLimiterFactory> $limiterFactories
     */
    public function __construct(
        private array $limiterFactories,
        private ApiRateLimitRequestMatcher $requestMatcher = new ApiRateLimitRequestMatcher(),
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->requestMatcher->supports($request)) {
            return;
        }

        foreach ($this->requestMatcher->resolveEndpointLimiters($request) as $target) {
            if (!$this->consumeLimiter($event, $target['name'], $target['key'])) {
                return;
            }
        }

        $globalTarget = $this->requestMatcher->resolveGlobalLimiter($request);
        $this->consumeLimiter($event, $globalTarget['name'], $globalTarget['key']);
    }

    private function consumeLimiter(
        RequestEvent $event,
        string $limiterName,
        string $key
    ): bool {
        $rateLimiterFactory = $this->limiterFactories[$limiterName] ?? null;
        if (!$rateLimiterFactory instanceof RateLimiterFactory) {
            throw new LogicException(sprintf('Rate limiter "%s" is not configured.', $limiterName));
        }

        $rateLimit = $rateLimiterFactory->create($key)->consume(1);
        if ($rateLimit->isAccepted()) {
            return true;
        }

        $event->setResponse($this->buildTooManyRequestsResponse($rateLimit));

        return false;
    }

    private function buildTooManyRequestsResponse(RateLimit $rateLimit): JsonResponse
    {
        $retryAfter = max(
            1,
            $rateLimit->getRetryAfter()->getTimestamp() - time()
        );

        return new JsonResponse(
            [
                'type' => '/errors/429',
                'title' => 'Too Many Requests',
                'detail' => 'Rate limit exceeded. Please try again later.',
                'status' => Response::HTTP_TOO_MANY_REQUESTS,
            ],
            Response::HTTP_TOO_MANY_REQUESTS,
            [
                'Content-Type' => 'application/problem+json',
                'Retry-After' => (string) $retryAfter,
            ]
        );
    }
}
