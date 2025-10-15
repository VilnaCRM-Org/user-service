<?php

declare(strict_types=1);

namespace App\Shared\Application\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class JsonRequestContentProvider
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function content(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request) {
            return null;
        }

        return trim((string) $request->getContent());
    }
}
