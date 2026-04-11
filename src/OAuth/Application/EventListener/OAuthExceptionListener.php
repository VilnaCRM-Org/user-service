<?php

declare(strict_types=1);

namespace App\OAuth\Application\EventListener;

use App\OAuth\Domain\Exception\InvalidStateException;
use App\OAuth\Domain\Exception\MissingOAuthParametersException;
use App\OAuth\Domain\Exception\OAuthEmailUnavailableException;
use App\OAuth\Domain\Exception\OAuthProviderException;
use App\OAuth\Domain\Exception\ProviderMismatchException;
use App\OAuth\Domain\Exception\StateExpiredException;
use App\OAuth\Domain\Exception\UnsupportedProviderException;
use App\OAuth\Domain\Exception\UnverifiedProviderEmailException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * @psalm-api
 */
final class OAuthExceptionListener
{
    private const ERROR_CODE_MAP = [
        UnsupportedProviderException::class => [
            'error_code' => 'unsupported_provider',
            'status' => Response::HTTP_BAD_REQUEST,
        ],
        MissingOAuthParametersException::class => [
            'error_code' => 'missing_oauth_parameters',
            'status' => Response::HTTP_BAD_REQUEST,
        ],
        ProviderMismatchException::class => [
            'error_code' => 'provider_mismatch',
            'status' => Response::HTTP_BAD_REQUEST,
        ],
        InvalidStateException::class => [
            'error_code' => 'invalid_state',
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
        ],
        StateExpiredException::class => [
            'error_code' => 'state_expired',
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
        ],
        OAuthEmailUnavailableException::class => [
            'error_code' => 'provider_email_unavailable',
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
        ],
        UnverifiedProviderEmailException::class => [
            'error_code' => 'unverified_provider_email',
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
        ],
        OAuthProviderException::class => [
            'error_code' => 'provider_unavailable',
            'status' => Response::HTTP_SERVICE_UNAVAILABLE,
        ],
    ];

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $mapping = self::ERROR_CODE_MAP[$exception::class] ?? null;

        if ($mapping === null) {
            return;
        }

        $status = $mapping['status'];

        $event->setResponse(new JsonResponse(
            [
                'type' => "/errors/{$status}",
                'title' => 'An error occurred',
                'detail' => $exception->getMessage(),
                'status' => $status,
                'error_code' => $mapping['error_code'],
            ],
            $status,
            ['Content-Type' => 'application/problem+json']
        ));
    }
}
