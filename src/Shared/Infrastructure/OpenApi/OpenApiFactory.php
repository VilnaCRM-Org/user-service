<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
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

        $this->add500ResponseToAllEndpoints($openApi);

        return $openApi->withServers([new Model\Server('https://localhost')]);
    }

    private function add500ResponseToAllEndpoints(OpenApi $openApi): void
    {
        $standardResponse500 = $this->serverErrorResponseFactory->getResponse();

        foreach (array_keys($openApi->getPaths()->getPaths()) as $path) {
            $pathItem = $openApi->getPaths()->getPath($path);
            $operationGet = $pathItem->getGet();
            $operationPost = $pathItem->getPost();
            $operationPut = $pathItem->getPut();
            $operationPatch = $pathItem->getPatch();
            $operationDelete = $pathItem->getDelete();

            if ($operationGet) {
                $pathItem = $pathItem->withGet($operationGet->withResponse(
                    HttpResponse::HTTP_INTERNAL_SERVER_ERROR,
                    $standardResponse500
                ));
            }
            if ($operationPost) {
                $pathItem = $pathItem->withPost($operationPost->withResponse(
                    HttpResponse::HTTP_INTERNAL_SERVER_ERROR,
                    $standardResponse500
                ));
            }
            if ($operationPut) {
                $pathItem = $pathItem->withPut($operationPut->withResponse(
                    HttpResponse::HTTP_INTERNAL_SERVER_ERROR,
                    $standardResponse500
                ));
            }
            if ($operationPatch) {
                $pathItem = $pathItem->withPatch($operationPatch->withResponse(
                    HttpResponse::HTTP_INTERNAL_SERVER_ERROR,
                    $standardResponse500
                ));
            }
            if ($operationDelete) {
                $pathItem = $pathItem->withDelete(
                    $operationDelete->withResponse(
                        HttpResponse::HTTP_INTERNAL_SERVER_ERROR,
                        $standardResponse500
                    )
                );
            }

            $openApi->getPaths()->addPath($path, $pathItem);
        }
    }
}
