<?php

declare(strict_types=1);

namespace App\Shared\Application;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ApiResource\Error;
use ApiPlatform\State\ProviderInterface;
use App\User\Domain\Exception\DomainException;
use GraphQL\Error\FormattedError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * @implements ProviderInterface<Error>
 */
final readonly class ErrorProvider extends AbstractErrorHandler implements ProviderInterface
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

        if ($status >= HttpResponse::HTTP_INTERNAL_SERVER_ERROR) {
            if ($this->isGraphQLRequest($request)) {
                $error = $this->provideGraphQLInternalServerError(
                    $exception,
                    $internalErrorText
                );
            } else {
                $error = $this->provideRESTInternalServerError(
                    $exception,
                    $internalErrorText,
                    $status,
                );
            }
        } elseif ($exception instanceof DomainException) {
            $error = $this->provideRESTDomainException($exception, $status);
        } else {
            $error = $this->provideHttpException($exception, $status);
        }

        return $error;
    }

    private function provideHttpException(
        Throwable $exception,
        int $status
    ): Error {
        $error = Error::createFromException($exception, $status);

        if ($exception instanceof NotFoundHttpException) {
            $error->setDetail($this->translator->trans('error.not.found.http'));
        }

        return $error;
    }

    /**
     * @return array<string,array<string>>
     */
    private function provideGraphQLInternalServerError(
        Throwable $exception,
        string $message
    ): array {
        $error = FormattedError::createFromException($exception);
        $error['message'] = $message;
        $this->addInternalCategoryIfMissing($error);
        return $error;
    }

    private function provideRESTInternalServerError(
        Throwable $exception,
        string $message,
        int $status
    ): Error {
        $error = Error::createFromException($exception, $status);
        $error->setDetail($message);

        return $error;
    }

    private function provideRESTDomainException(
        DomainException $exception,
        int $status
    ): Error {
        $error = Error::createFromException($exception, $status);
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
}
