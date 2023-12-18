<?php

namespace App\Shared\Infrastructure;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ApiResource\Error;
use ApiPlatform\State\ProviderInterface;

final class ErrorProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $request = $context['request'];
        $format = $request->getRequestFormat();
        $exception = $request->attributes->get('exception');

        $status = $operation->getStatus() ?? 500;
        // You don't have to use this, you can use a Response, an array or any object (preferably a resource that API Platform can handle).
        $error = Error::createFromException($exception, $status);

        // care about hiding informations as this can be a security leak
        if ($status >= 500) {
            $error->setDetail('Something went wrong');
        }

        return $error;
    }
}