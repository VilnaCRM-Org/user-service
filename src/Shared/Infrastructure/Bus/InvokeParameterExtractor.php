<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use LogicException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

final class InvokeParameterExtractor
{
    public function extract(object|string $class): ?string
    {
        $reflector = new ReflectionClass($class);

        if (!$reflector->hasMethod('__invoke')) {
            return null;
        }

        $method = $reflector->getMethod('__invoke');

        if (!$this->hasOnlyOneParameter($method)) {
            return null;
        }

        return $this->firstParameterClassFrom($method);
    }

    private function firstParameterClassFrom(ReflectionMethod $method): ?string
    {
        $firstParameterType = $method->getParameters()[0]->getType();

        $this->ensureTypeHintExists($firstParameterType);

        if (!$firstParameterType instanceof ReflectionNamedType) {
            return null;
        }

        return $firstParameterType->getName();
    }

    private function ensureTypeHintExists(
        ?\ReflectionType $type
    ): void {
        if ($this->isMissingOrMixed($type)) {
            throw new LogicException(
                'Missing type hint for the first parameter of __invoke'
            );
        }
    }

    private function isMissingOrMixed(?\ReflectionType $type): bool
    {
        return $type === null
            || ($type instanceof ReflectionNamedType
                && $type->getName() === 'mixed');
    }

    private function hasOnlyOneParameter(ReflectionMethod $method): bool
    {
        return $method->getNumberOfParameters() === 1;
    }
}
