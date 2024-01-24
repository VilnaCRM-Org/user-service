<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\EndpointFactory;

use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Infrastructure\OpenApi\Factory\RequestFactory\OAuthTokenRequestFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\InvalidClientCredentialsResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\OAuthTokenReturnedResponseFactory;
use App\Shared\Infrastructure\OpenApi\Factory\ResponseFactory\UnsupportedGrantTypeResponseFactory;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class OAuthTokenEndpointFactory implements AbstractEndpointFactory
{
    private const ENDPOINT_URI = '/api/oauth/token';

    public function __construct(
        private UnsupportedGrantTypeResponseFactory $unsupportedFactory,
        private InvalidClientCredentialsResponseFactory $invalidCredsFactory,
        private OAuthTokenReturnedResponseFactory $tokenReturnedResponseFactory,
        private OAuthTokenRequestFactory $tokenRequestFactory
    ) {
    }

    public function createEndpoint(OpenApi $openApi): void
    {
        $openApi->getPaths()->addPath(
            self::ENDPOINT_URI,
            new Model\PathItem(
                summary: 'Requests for access token',
                description: 'Requests for access token',
                post: new Model\Operation(
                    tags: ['OAuth'],
                    responses: [
                        HttpResponse::HTTP_OK => $this->tokenReturnedResponseFactory->getResponse(),
                        HttpResponse::HTTP_BAD_REQUEST => $this->unsupportedFactory->getResponse(),
                        HttpResponse::HTTP_UNAUTHORIZED => $this->invalidCredsFactory->getResponse(),
                    ],
                    requestBody: $this->tokenRequestFactory->getRequest(),
                )
            )
        );
    }
}
