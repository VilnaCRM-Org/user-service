<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * @psalm-suppress UnusedClass - registered via services.yaml
 */
final class OAuthUnsupportedResponseTypeListener
{
    private const UNSUPPORTED_GRANT_TYPE = 'error=unsupported_grant_type';
    private const UNSUPPORTED_RESPONSE_TYPE = 'error=unsupported_response_type';
    private const SUPPORTED_RESPONSE_TYPES = ['code', 'token'];

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->attributes->get('_route') !== 'oauth2_authorize') {
            return;
        }

        $responseType = $request->query->get('response_type');
        if ($responseType === null || in_array($responseType, self::SUPPORTED_RESPONSE_TYPES, true)) {
            return;
        }

        $response = $event->getResponse();
        $location = $response->headers->get('Location');
        if ($location === null || !str_contains($location, self::UNSUPPORTED_GRANT_TYPE)) {
            return;
        }

        $response->headers->set(
            'Location',
            str_replace(self::UNSUPPORTED_GRANT_TYPE, self::UNSUPPORTED_RESPONSE_TYPE, $location)
        );
    }
}
