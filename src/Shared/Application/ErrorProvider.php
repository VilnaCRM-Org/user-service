<?php

declare(strict_types=1);

namespace App\Shared\Application;

use ApiPlatform\Metadata\Exception\HttpExceptionInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ApiResource\Error;
use ApiPlatform\State\ProviderInterface;
use App\User\Domain\Exception\DomainException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface as SymfonyHttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * @implements ProviderInterface<Error>
 */
final readonly class ErrorProvider implements ProviderInterface
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    /**
     * @param array<string,string> $uriVariables
     * @param array<string,array<string>> $context
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): object|array|null {
        $internalErrorText = $this->translator->trans('error.internal');
        /** @var Request $request */
        $request = $context['request'];
        assert(is_object($request));
        assert($request instanceof Request);

        $status = $operation->getStatus() ??
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR;

        return $this->getError($request, $status, $internalErrorText);
    }

    private function getError(
        Request $request,
        int $status,
        string $internalErrorText
    ): array|Error {
        $exception = $request->attributes->get('exception');
        assert($exception instanceof Throwable);

        if ($status >= HttpResponse::HTTP_INTERNAL_SERVER_ERROR) {
            return $this->provideInternalServerError(
                $request,
                $exception,
                $internalErrorText,
                $status
            );
        }

        return $this->provideDomainOrHttpError($exception, $status);
    }

    private function provideInternalServerError(
        Request $request,
        Throwable $exception,
        string $internalErrorText,
        int $status
    ): array|Error {
        if ($this->isGraphQLRequest($request)) {
            return $this->provideGraphQLInternalServerError(
                $internalErrorText
            );
        }

        return $this->provideRESTInternalServerError(
            $exception,
            $internalErrorText,
            $status
        );
    }

    private function provideDomainOrHttpError(
        Throwable $exception,
        int $status
    ): Error {
        if ($exception instanceof DomainException) {
            return $this->provideRESTDomainException($exception, $status);
        }

        return $this->provideHttpException($exception, $status);
    }

    private function provideHttpException(
        Throwable $exception,
        int $status
    ): Error {
        $error = $this->createErrorFromException($exception, $status);

        if ($exception instanceof NotFoundHttpException) {
            $error->setDetail($this->translator->trans('error.not.found.http'));
        }

        return $error;
    }

    /**
     * @return array<string,array<string>>
     */
    private function provideGraphQLInternalServerError(string $message): array
    {
        return ['message' => $message];
    }

    private function provideRESTInternalServerError(
        Throwable $exception,
        string $message,
        int $status
    ): Error {
        $error = $this->createErrorFromException($exception, $status);
        $error->setDetail($message);

        return $error;
    }

    private function provideRESTDomainException(
        DomainException $exception,
        int $status
    ): Error {
        $error = $this->createErrorFromException($exception, $status);
        $error->setDetail($this->translator->trans(
            $exception->getTranslationTemplate(),
            $exception->getTranslationArgs()
        ));

        return $error;
    }

    private function isGraphQLRequest(Request $request): bool
    {
        return str_contains($request->getRequestUri(), 'graphql');
    }

    private function createErrorFromException(
        Throwable $exception,
        int $status
    ): Error {
        $headers = [];

        if (
            $exception instanceof SymfonyHttpExceptionInterface
            || $exception instanceof HttpExceptionInterface
        ) {
            $headers = $exception->getHeaders();
        }

        return new Error(
            title: 'An error occurred',
            detail: $exception->getMessage(),
            status: $status,
            originalTrace: $exception->getTrace(),
            type: "/errors/{$status}",
            headers: $headers,
            previous: $exception->getPrevious()
        );
    }
}
