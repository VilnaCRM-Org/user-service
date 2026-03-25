<?php

declare(strict_types=1);

namespace App\Shared\Application\Provider\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class RouteIdentifierProvider
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function identifier(string $attribute): ?string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request) {
            return null;
        }

        return $this->normalizeAttributeValue(
            $request->attributes->get($attribute)
        );
    }

    private function normalizeAttributeValue(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        if ($normalized === '') {
            return null;
        }

        return $normalized;
    }
}
