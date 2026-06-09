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
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * @psalm-api
 */
final class OAuthExceptionListener
{
    /**
     * Maps each handled OAuth exception to a stable client contract.
     *
     * The `detail` values are static, provider-agnostic, and safe to expose to
     * unauthenticated clients. Raw exception messages are never returned because
     * they may embed upstream provider error bodies, request URLs (with
     * client_id query parameters), internal hostnames, or HTTP status lines
     * (CWE-209). The full exception message and trace are logged server-side.
     */
    private const ERROR_CODE_MAP = [
        UnsupportedProviderException::class => [
            'error_code' => 'unsupported_provider',
            'status' => Response::HTTP_BAD_REQUEST,
            'detail' => 'The requested authentication provider is not supported.',
        ],
        MissingOAuthParametersException::class => [
            'error_code' => 'missing_oauth_parameters',
            'status' => Response::HTTP_BAD_REQUEST,
            'detail' => 'The authentication request is missing required parameters.',
        ],
        ProviderMismatchException::class => [
            'error_code' => 'provider_mismatch',
            'status' => Response::HTTP_BAD_REQUEST,
            'detail' => 'The authentication provider does not match the original request.',
        ],
        InvalidStateException::class => [
            'error_code' => 'invalid_state',
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'detail' => 'The authentication request could not be verified.',
        ],
        StateExpiredException::class => [
            'error_code' => 'state_expired',
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'detail' => 'The authentication request has expired. Please try again.',
        ],
        OAuthEmailUnavailableException::class => [
            'error_code' => 'provider_email_unavailable',
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'detail' => 'The authentication provider did not return an email address.',
        ],
        UnverifiedProviderEmailException::class => [
            'error_code' => 'unverified_provider_email',
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'detail' => 'The authentication provider did not return a verified email address.',
        ],
        OAuthProviderException::class => [
            'error_code' => 'provider_unavailable',
            'status' => Response::HTTP_SERVICE_UNAVAILABLE,
            'detail' => 'The authentication provider is currently unavailable. Please try again later.',
        ],
    ];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $mapping = self::ERROR_CODE_MAP[$exception::class] ?? null;

        if ($mapping === null) {
            return;
        }

        $this->logger->error('OAuth flow failed: ' . $exception->getMessage(), [
            'error_code' => $mapping['error_code'],
            'exception' => $exception,
        ]);

        $status = $mapping['status'];

        $event->setResponse(new JsonResponse(
            [
                'type' => "/errors/{$status}",
                'title' => 'An error occurred',
                'detail' => $mapping['detail'],
                'status' => $status,
                'error_code' => $mapping['error_code'],
            ],
            $status,
            ['Content-Type' => 'application/problem+json']
        ));
    }
}
