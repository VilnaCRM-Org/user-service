<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Augmenter;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class ServerErrorResponseAugmenter
{
    private const OPERATIONS = ['Get', 'Post', 'Put', 'Patch', 'Delete'];

    public function __construct(
        private InternalErrorFactory $internalErrorFactory
    ) {
    }

    public function augment(OpenApi $openApi): void
    {
        $response = $this->internalErrorFactory->getResponse();

        foreach (array_keys($openApi->getPaths()->getPaths()) as $path) {
            $this->addServerErrorResponseToPath($openApi, $path, $response);
        }
    }

    private function addServerErrorResponseToPath(
        OpenApi $openApi,
        string $path,
        Response $response
    ): void {
        $pathItem = $openApi->getPaths()->getPath($path);
        $pathItem = $this->decoratePathItemOperations($pathItem, $response);
        $openApi->getPaths()->addPath($path, $pathItem);
    }

    private function decoratePathItemOperations(
        PathItem $pathItem,
        Response $response
    ): PathItem {
        $mutatedPathItem = $pathItem;

        foreach (self::OPERATIONS as $operation) {
            $getter = 'get' . $operation;
            $with = 'with' . $operation;

            $mutatedPathItem = $mutatedPathItem->$with(
                $this->addErrorResponseToOperation(
                    $pathItem->$getter(),
                    $response
                )
            );
        }

        return $mutatedPathItem;
    }

    private function addErrorResponseToOperation(
        ?Operation $operation,
        Response $response
    ): ?Operation {
        if ($operation === null) {
            return null;
        }

        return $operation->withResponse(
            HttpResponse::HTTP_INTERNAL_SERVER_ERROR,
            $response
        );
    }
}
