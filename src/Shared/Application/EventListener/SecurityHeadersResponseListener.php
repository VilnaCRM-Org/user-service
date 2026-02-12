<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final readonly class SecurityHeadersResponseListener
{
    private const HSTS_VALUE = 'max-age=31536000; includeSubDomains';
    private const X_CONTENT_TYPE_OPTIONS_VALUE = 'nosniff';
    private const X_FRAME_OPTIONS_VALUE = 'DENY';
    private const REFERRER_POLICY_VALUE = 'strict-origin-when-cross-origin';
    private const CONTENT_SECURITY_POLICY_VALUE = "default-src 'none'; frame-ancestors 'none'";
    private const PERMISSIONS_POLICY_DIRECTIVES = [
        'camera=()',
        'microphone=()',
        'geolocation=()',
        'payment=()',
        'usb=()',
    ];

    public function __invoke(ResponseEvent $event): void
    {
        if ($event->getRequestType() !== HttpKernelInterface::MAIN_REQUEST) {
            return;
        }

        $headers = $event->getResponse()->headers;
        $headers->set('Strict-Transport-Security', self::HSTS_VALUE);
        $headers->set('X-Content-Type-Options', self::X_CONTENT_TYPE_OPTIONS_VALUE);
        $headers->set('X-Frame-Options', self::X_FRAME_OPTIONS_VALUE);
        $headers->set('Referrer-Policy', self::REFERRER_POLICY_VALUE);
        $headers->set('Content-Security-Policy', self::CONTENT_SECURITY_POLICY_VALUE);
        $headers->set('Permissions-Policy', implode(', ', self::PERMISSIONS_POLICY_DIRECTIVES));
        $headers->remove('Server');
    }
}
