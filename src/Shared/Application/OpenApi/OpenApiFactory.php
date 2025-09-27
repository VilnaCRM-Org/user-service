<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Response;
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
        private InternalErrorFactory $serverErrorResponseFactory,
        private string $serverUrl
    ) {
    }

    /**
     * @param array<string, string> $context
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);
        $openApi = $openApi->withComponents($this->augmentComponents($openApi));

        foreach ($this->endpointFactories as $endpointFactory) {
            $endpointFactory->createEndpoint($openApi);
        }

        $this->addServerErrorResponseToAllEndpoints($openApi);

        return $openApi->withServers([
            new Model\Server($this->serverUrl),
        ])->withSecurity([
            ['OAuth2' => []],
        ]);
    }

    private function augmentComponents(OpenApi $openApi): Components
    {
        $components = $openApi->getComponents() ?? new Components();
        $securitySchemes = $components->getSecuritySchemes()
            ?? new ArrayObject();
        $securitySchemes['OAuth2'] = $this->createOAuth2Scheme();

        return $components->withSecuritySchemes($securitySchemes);
    }

    /**
     * @return array<string>
     */
    private function createOAuth2Scheme(): array
    {
        return [
            'type' => 'oauth2',
            'flows' => [
                'authorizationCode' => $this->createOAuth2CodeFlow(),
            ],
        ];
    }

    /**
     * @return array<string>
     */
    private function createOAuth2CodeFlow(): array
    {
        return [
            'authorizationUrl' => 'https://localhost/api/oauth/dialog',
            'tokenUrl' => 'https://localhost/api/oauth/token',
            'scopes' => [
                'write:pets' => 'modify pets in your account',
                'read:pets' => 'read your pets',
            ],
        ];
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
