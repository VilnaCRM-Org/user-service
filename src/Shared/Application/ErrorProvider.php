<?php

declare(strict_types=1);

namespace App\Shared\Application;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ApiResource\Error;
use ApiPlatform\State\ProviderInterface;
use App\User\Domain\Exception\DomainException;
use GraphQL\Error\FormattedError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        $request = $context['request'];
        $exception = $request->attributes->get('exception');

        $status = $operation->getStatus() ?? 500;

        if ($this->isGraphQLRequest($request)) {
            $error = FormattedError::createFromException($exception);
            $error['message'] = $internalErrorText;
        } else {
            $error = Error::createFromException($exception, $status);

            if ($status >= 500) {
                $error->setDetail($internalErrorText);
            } elseif ($exception instanceof DomainException) {
                $error->setDetail($this->translator->trans(
                    $exception->getTranslationTemplate(),
                    $exception->getTranslationArgs()
                ));
            }
        }

        return $error;
    }

    private function isGraphQLRequest(Request $request): bool
    {
        return str_contains($request->getRequestUri(), 'graphql');
    }
}
