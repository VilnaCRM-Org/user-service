<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\EndpointFactory;

use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\TokenNotFoundResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\UserConfirmedResponseFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ConfirmUserEndpointFactory implements AbstractEndpointFactory
{
    public function __construct(
        private TokenNotFoundResponseFactory $tokenNotFoundResponseFactory,
        private UserConfirmedResponseFactory $userConfirmedResponseFactory
    ) {
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $pathItem = $openApi->getPaths()->getPath('/api/users/confirm');
        $operationPatch = $pathItem->getPatch();

        $userConfirmedResponse =
            $this->userConfirmedResponseFactory->getResponse();
        $tokenNotFoundResponse =
            $this->tokenNotFoundResponseFactory->getResponse();

        $openApi->getPaths()->addPath('/api/users/confirm', $pathItem->withPatch(
            $operationPatch->withDescription('Confirms the User')->withSummary('Confirms the User')
                ->withResponses(
                    [
                        HttpResponse::HTTP_OK => $userConfirmedResponse,
                        HttpResponse::HTTP_NOT_FOUND => $tokenNotFoundResponse,
                    ],
                )
        ));

    }
}
