<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use RuntimeException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 0)]
final class OAuthAuthorizationExceptionListener
{
    private const EXPECTED_MESSAGE =
        'A logged in user is required to resolve the authorization request.';
    private const ERROR_DESCRIPTION =
        'User authentication is required to resolve the authorization request.';

    public function __invoke(ExceptionEvent $event): void
    {
        if (!$this->shouldHandle($event)) {
            return;
        }

        $event->setResponse(new JsonResponse(
            [
                'error' => 'invalid_client',
                'error_description' => self::ERROR_DESCRIPTION,
            ],
            JsonResponse::HTTP_UNAUTHORIZED
        ));
    }

    private function shouldHandle(ExceptionEvent $event): bool
    {
        return match (true) {
            !$event->isMainRequest() => false,
            $event->getRequest()->attributes->get('_route')
                !== 'oauth2_authorize' => false,
            default => $this->isExpectedException($event->getThrowable()),
        };
    }

    private function isExpectedException(Throwable $throwable): bool
    {
        if (!$throwable instanceof RuntimeException) {
            return false;
        }

        return $throwable->getMessage() === self::EXPECTED_MESSAGE;
    }
}
