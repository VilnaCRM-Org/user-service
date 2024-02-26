<?php

declare(strict_types=1);

namespace App\Shared\Application;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ApiResource\Error;
use ApiPlatform\State\ProviderInterface;

/**
 * @implements ProviderInterface<Error>
 */
final class ErrorProvider implements ProviderInterface
{
    /**
     * @param array<string,string> $uriVariables
     * @param array<string,string> $context
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): object|array|null {
        $request = $context['request'];
        $exception = $request->attributes->get('exception');

        $status = $operation->getStatus() ?? 500;

        $error = Error::createFromException($exception, $status);

        // care about hiding informations as this can be a security leak
        if ($status >= 500) {
            $error->setDetail('Something went wrong');
        }

        return $error;
    }
}
