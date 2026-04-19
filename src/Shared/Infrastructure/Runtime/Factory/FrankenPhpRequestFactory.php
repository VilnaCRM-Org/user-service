<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime\Factory;

use Closure;
use Symfony\Component\HttpFoundation\Request;

final class FrankenPhpRequestFactory
{
    private readonly FrankenPhpLegacyBodyRequestFactory $legacyBodyRequestFactory;
    private readonly FrankenPhpParsedBodyRequestFactory $parsedBodyRequestFactory;
    private readonly FrankenPhpRequestGlobalsReaderInterface $requestGlobalsReader;

    public function __construct(
        private readonly ?Closure $bodyParserChecker = null,
        ?FrankenPhpRequestGlobalsReaderInterface $requestGlobalsReader = null,
    ) {
        $this->legacyBodyRequestFactory = new FrankenPhpLegacyBodyRequestFactory();
        $this->parsedBodyRequestFactory = new FrankenPhpParsedBodyRequestFactory();
        $this->requestGlobalsReader = $requestGlobalsReader ?? new FrankenPhpRequestGlobalsReader();
    }

    public function createFromGlobals(): Request
    {
        $request = $this->createBaseRequest();

        if (!$this->requiresBodyParsing($request)) {
            return $request;
        }

        if ($this->requestBodyParserIsAvailable()) {
            return $this->parsedBodyRequestFactory->create($request);
        }

        return $this->legacyBodyRequestFactory->create($request);
    }

    public function createBaseRequest(): Request
    {
        return $this->requestGlobalsReader->readRequest();
    }

    private function requestBodyParserIsAvailable(): bool
    {
        $checker = $this->bodyParserChecker
            ?? static fn (): bool => \function_exists('request_parse_body');

        return $checker();
    }

    private function requiresBodyParsing(Request $request): bool
    {
        return \in_array($request->getMethod(), ['PUT', 'DELETE', 'PATCH', 'QUERY'], true);
    }
}
