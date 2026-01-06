<?php

declare(strict_types=1);

namespace App\Shared\Application\Resolver;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

final readonly class HttpExceptionDetailResolver
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    public function resolve(Throwable $exception): string
    {
        if ($exception instanceof NotFoundHttpException) {
            return $this->translator->trans('error.not.found.http');
        }

        return $exception->getMessage();
    }
}
