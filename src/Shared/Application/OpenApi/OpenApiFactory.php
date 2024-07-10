<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\Model\SecurityScheme;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\AbstractEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use ArrayObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class OpenApiFactory implements OpenApiFactoryInterface
{
    /**
     * @param iterable<AbstractEndpointFactory> $endpointFactories
     */
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        private iterable $endpointFactories,
        private InternalErrorFactory $serverErrorResponseFactory
    ) {
    }

    /**
     * @param array<string, string> $context
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);
        $openApi = $this->addSecurityScheme($openApi);
        $this->addEndpoints($openApi);
        $this->addServerErrorResponseToAllEndpoints($openApi);
        return $this->setUpServers($openApi);
    }

    private function setUpServers(OpenApi $openApi): OpenApi
    {
        return $openApi->withServers([new Model\Server('https://localhost')]);
    }

    private function addSecurityScheme(OpenApi $openApi): OpenApi
    {
        $securityScheme = new SecurityScheme(
            type: 'http',
            description: 'JWT Bearer Token authentication',
            scheme: 'bearer',
            bearerFormat: 'JWT'
        );

        $components = $openApi->getComponents();
        $securitySchemes = $components->getSecuritySchemes();
        $securitySchemes['BearerAuth'] = $securityScheme;

        return $openApi->withComponents(
            $components->withSecuritySchemes(new ArrayObject($securitySchemes))
        );
    }

    private function addEndpoints(OpenApi $openApi): void
    {
        foreach ($this->endpointFactories as $endpointFactory) {
            $endpointFactory->createEndpoint($openApi);
        }
    }

    private function addServerErrorResponseToAllEndpoints(
        OpenApi $openApi
    ): void {
        $serverErrorResponse = $this->serverErrorResponseFactory->getResponse();
        foreach (array_keys($openApi->getPaths()->getPaths()) as $path) {
            $this->addServerErrorResponseToPath(
                $openApi,
                $path,
                $serverErrorResponse
            );
        }
    }

    private function addServerErrorResponseToPath(
        OpenApi $openApi,
        string $path,
        Response $response
    ): void {
        $pathItem = $openApi->getPaths()->getPath($path);
        $pathItem = $this->processPathItemOperations($pathItem, $response);
        $openApi->getPaths()->addPath($path, $pathItem);
    }

    private function processPathItemOperations(
        PathItem $pathItem,
        Response $standardResponse
    ): PathItem {
        return $pathItem->withGet($this->addErrorResponseToOperation(
            $pathItem->getGet(),
            $standardResponse
        ))
            ->withPost($this->addErrorResponseToOperation(
                $pathItem->getPost(),
                $standardResponse
            ))
            ->withPut($this->addErrorResponseToOperation(
                $pathItem->getPut(),
                $standardResponse
            ))
            ->withPatch($this->addErrorResponseToOperation(
                $pathItem->getPatch(),
                $standardResponse
            ))
            ->withDelete($this->addErrorResponseToOperation(
                $pathItem->getDelete(),
                $standardResponse
            ));
    }

    private function addErrorResponseToOperation(
        ?Operation $operation,
        Response $standardResponse
    ): ?Operation {
        return $operation?->withResponse(
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR,
            $standardResponse
        );
    }
}
