<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\ValueObject\Header;

final class OAuthRedirectFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    #[\Override]
    public function getResponse(): Response
    {
        $locationHeader = new Header(
            'Location',
            'The URI to redirect to for user authorization',
            'string',
            'uri',
            'https://example.com?code=e7f8c62113a4'
        );

        return $this->responseBuilder->build(
            'Redirect to the provided redirect URI with authorization code.',
            [],
            [$locationHeader]
        );
    }
}
