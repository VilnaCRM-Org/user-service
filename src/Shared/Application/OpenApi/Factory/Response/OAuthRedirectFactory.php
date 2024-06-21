<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\Header;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;

final class OAuthRedirectFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        $locationHeader = new Header(
            'Location',
            'The URI to redirect to for user authorization',
            'string',
            'uri',
            'https://example.com/oauth/callback?code=e7f8c62113a4'
        );

        return $this->responseBuilder->build(
            'Redirect to the provided redirect URI with authorization code.',
            [],
            [$locationHeader]
        );
    }
}
