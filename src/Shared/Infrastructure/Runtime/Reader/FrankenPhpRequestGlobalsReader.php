<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime\Reader;

use Symfony\Component\HttpFoundation\Request;

final class FrankenPhpRequestGlobalsReader implements FrankenPhpRequestGlobalsReaderInterface
{
    private readonly HttpFoundationRequestFactory $requestFactory;

    public function __construct(?HttpFoundationRequestFactory $requestFactory = null)
    {
        $this->requestFactory = $requestFactory ?? new HttpFoundationRequestFactory();
    }

    /**
     * @psalm-suppress ImpureMethodCall
     */
    #[\Override]
    public function readRequest(): Request
    {
        return $this->requestFactory->createFromGlobals();
    }
}
