<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Infrastructure\OpenApi\Factory\EndpointFactory\AbstractEndpointFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\InternalServerErrorResponseFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class OpenApiFactory implements OpenApiFactoryInterface
{
    /**
     * @param OpenApiFactoryInterface $decorated
     * @param iterable<AbstractEndpointFactory> $endpointFactories
     */
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        private iterable $endpointFactories,
        private InternalServerErrorResponseFactory $serverErrorResponseFactory
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        foreach ($this->endpointFactories as $endpointFactory) {
            $endpointFactory->createEndpoint($openApi);
        }

        $this->addServerErrorResponseToAllEndpoints($openApi);

        return $openApi->withServers([new Model\Server('https://localhost')]);
    }

    private function addServerErrorResponseToAllEndpoints(OpenApi $openApi): void
    {
        $serverErrorResponse = $this->serverErrorResponseFactory->getResponse();

        foreach (array_keys($openApi->getPaths()->getPaths()) as $path) {
            $this->addServerErrorResponseToPath($openApi, $path, $serverErrorResponse);
        }
    }

    private function addServerErrorResponseToPath(OpenApi $openApi, string $path, Response $response): void
    {
        $pathItem = $openApi->getPaths()->getPath($path);
        $pathItem = $this->processPathItemOperations($pathItem, $response);
        $openApi->getPaths()->addPath($path, $pathItem);
    }

    private function processPathItemOperations(PathItem $pathItem, Response $standardResponse): PathItem
    {
        return $pathItem
            ->withGet($this->addErrorResponseToOperation($pathItem->getGet(), $standardResponse))
            ->withPost($this->addErrorResponseToOperation($pathItem->getPost(), $standardResponse))
            ->withPut($this->addErrorResponseToOperation($pathItem->getPut(), $standardResponse))
            ->withPatch($this->addErrorResponseToOperation($pathItem->getPatch(), $standardResponse))
            ->withDelete($this->addErrorResponseToOperation($pathItem->getDelete(), $standardResponse));
    }

    private function addErrorResponseToOperation(?Operation $operation, Response $standardResponse): ?Operation
    {
        return $operation?->withResponse(HttpResponse::HTTP_INTERNAL_SERVER_ERROR, $standardResponse);
    }
}
