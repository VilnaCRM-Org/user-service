<?php

declare(strict_types=1);

namespace App\User\Application\EventListener;

use App\Shared\Application\Resolver\RateLimit\ApiRateLimitGraphQlQueryInspector;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final readonly class PasskeyProductionReadinessListener
{
    private const GRAPHQL_PATH = '/api/graphql';
    private const PASSKEY_GRAPHQL_FIELDS = [
        'passkeySignUpOptionsUser',
        'passkeySignUpCompleteUser',
        'passkeySignInOptionsUser',
        'passkeySignInCompleteUser',
        'passkeyRegistrationOptionsUser',
        'passkeyRegistrationCompleteUser',
    ];
    private const TRAFFIC_DISABLED_DETAIL =
        'Passkey production traffic is disabled until production monitoring and alerts are ready.';
    private const MONITORING_NOT_READY_DETAIL_PREFIX =
        'Passkey production traffic requires latency, traffic, error-rate, ';
    private const MONITORING_NOT_READY_DETAIL_SUFFIX =
        'active-challenge, expired-challenge, and TTL-index monitoring before enablement.';
    private const MONITORING_NOT_READY_DETAIL =
        self::MONITORING_NOT_READY_DETAIL_PREFIX . self::MONITORING_NOT_READY_DETAIL_SUFFIX;

    public function __construct(
        private string $appEnv,
        private bool $productionTrafficEnabled,
        private bool $productionMonitoringReady,
        private ApiRateLimitGraphQlQueryInspector $graphQlQueryInspector,
        private PasskeyGraphQlRequestResolver $graphQlRequestResolver,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $detail = $this->resolveUnavailableDetail($event);
        if ($detail === null) {
            return;
        }

        $this->setUnavailableResponse($event, $detail);
    }

    private function resolveUnavailableDetail(RequestEvent $event): ?string
    {
        if (!$this->shouldCheckRequest($event)) {
            return null;
        }

        return $this->resolvePasskeyUnavailableDetail($event->getRequest());
    }

    private function shouldCheckRequest(RequestEvent $event): bool
    {
        return $event->isMainRequest() && $this->appEnv === 'prod';
    }

    private function resolvePasskeyUnavailableDetail(Request $request): ?string
    {
        if (!$this->isPasskeyTraffic($request)) {
            return null;
        }

        return $this->resolveProductionUnavailableDetail();
    }

    private function resolveProductionUnavailableDetail(): ?string
    {
        if (!$this->productionTrafficEnabled) {
            return self::TRAFFIC_DISABLED_DETAIL;
        }

        return $this->productionMonitoringReady
            ? null
            : self::MONITORING_NOT_READY_DETAIL;
    }

    private function isPasskeyTraffic(Request $request): bool
    {
        return $this->isPasskeyRestRequest($request)
            || $this->isPasskeyGraphQlMutation($request);
    }

    private function isPasskeyRestRequest(Request $request): bool
    {
        return $request->getMethod() === Request::METHOD_POST
            && preg_match(
                '#^/api/passkeys/(signup|register|signin)/(options|complete)$#',
                $request->getPathInfo()
            ) === 1;
    }

    private function isPasskeyGraphQlMutation(Request $request): bool
    {
        if ($request->getPathInfo() !== self::GRAPHQL_PATH) {
            return false;
        }

        $query = $this->graphQlRequestResolver->resolveQuery($request);
        if ($query === null) {
            return false;
        }

        $inspection = $this->graphQlQueryInspector->inspect(
            $query,
            $this->graphQlRequestResolver->resolveOperationName($request)
        );

        return $inspection !== null
            && $inspection->containsMutationField(self::PASSKEY_GRAPHQL_FIELDS);
    }

    private function setUnavailableResponse(RequestEvent $event, string $detail): void
    {
        $event->setResponse(new JsonResponse(
            [
                'type' => '/errors/passkey-production-readiness',
                'title' => 'Service Unavailable',
                'status' => Response::HTTP_SERVICE_UNAVAILABLE,
                'detail' => $detail,
            ],
            Response::HTTP_SERVICE_UNAVAILABLE,
            ['Content-Type' => 'application/problem+json']
        ));
    }
}
