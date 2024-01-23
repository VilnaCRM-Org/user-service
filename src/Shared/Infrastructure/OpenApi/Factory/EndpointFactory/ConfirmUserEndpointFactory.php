<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\EndpointFactory;

use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ConfirmUserEndpointFactory implements AbstractEndpointFactory
{
    public function createEndpoint(OpenApi $openApi): void
    {
        $pathItem = $openApi->getPaths()->getPath('/api/users/confirm');
        $operationPatch = $pathItem->getPatch();

        $openApi->getPaths()->addPath('/api/users/confirm', $pathItem->withPatch(
            $operationPatch->withDescription('Confirms the User')->withSummary('Confirms the User')
                ->withResponses(
                    [
                        HttpResponse::HTTP_OK => new Response(description: 'User confirmed', content: new \ArrayObject([
                            'application/json' => [
                                'example' => '',
                            ],
                        ]), ),
                        HttpResponse::HTTP_NOT_FOUND => new Response(description: 'Token not found or expired', content: new \ArrayObject([
                            'application/json' => [
                                'example' => '',
                            ],
                        ]), ),
                    ],
                )
        ));

    }
}
