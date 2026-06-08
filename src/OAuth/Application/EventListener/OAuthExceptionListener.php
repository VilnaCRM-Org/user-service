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
use App\User\Domain\Exception\DuplicateEmailException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Throwable;

/**
 * @psalm-api
 */
final class OAuthExceptionListener
{
    private const OAUTH_SOCIAL_CALLBACK_PATTERN = '#^/api/auth/social/[^/]+/callback$#';
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
        DuplicateEmailException::class => [
            'error_code' => 'duplicate_email',
            'status' => Response::HTTP_CONFLICT,
        ],
        OAuthProviderException::class => [
            'error_code' => 'provider_unavailable',
            'status' => Response::HTTP_SERVICE_UNAVAILABLE,
        ],
    ];

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $mapping = $this->mappingFor($exception);

        if ($mapping === null || $this->shouldIgnoreException($event, $exception)) {
            return;
        }

        $event->setResponse($this->problemResponse($exception, $mapping));
    }

    /**
     * @return array{error_code: string, status: int}|null
     */
    private function mappingFor(Throwable $exception): ?array
    {
        return self::ERROR_CODE_MAP[$exception::class] ?? null;
    }

    private function shouldIgnoreException(ExceptionEvent $event, Throwable $exception): bool
    {
        return $exception instanceof DuplicateEmailException
            && !$this->isOAuthSocialCallbackRequest($event);
    }

    /**
     * @param array{error_code: string, status: int} $mapping
     */
    private function problemResponse(Throwable $exception, array $mapping): JsonResponse
    {
        return new JsonResponse(
            [
                'type' => "/errors/{$mapping['status']}",
                'title' => 'An error occurred',
                'detail' => $this->detailFor($exception),
                'status' => $mapping['status'],
                'error_code' => $mapping['error_code'],
            ],
            $mapping['status'],
            ['Content-Type' => 'application/problem+json']
        );
    }

    private function detailFor(Throwable $exception): string
    {
        if ($exception instanceof DuplicateEmailException) {
            return 'Email address matches multiple local users; automatic linking is blocked.';
        }

        return $exception->getMessage();
    }

    private function isOAuthSocialCallbackRequest(ExceptionEvent $event): bool
    {
        return preg_match(
            self::OAUTH_SOCIAL_CALLBACK_PATTERN,
            $event->getRequest()->getPathInfo()
        ) === 1;
    }
}
