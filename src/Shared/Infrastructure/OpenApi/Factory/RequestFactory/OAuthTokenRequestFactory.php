<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi\Factory\RequestFactory;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Infrastructure\OpenApi\Builder\Parameter;
use App\Shared\Infrastructure\OpenApi\Builder\RequestBuilder;

final class OAuthTokenRequestFactory implements AbstractRequestFactory
{
    public function __construct(private RequestBuilder $requestBuilder)
    {
    }

    public function getRequest(): RequestBody
    {
        return $this->requestBuilder->build(
            [
                new Parameter('grant_type', 'string', 'authorization_code'),
                new Parameter('client_id', 'string', 'dc0bc6323f16fecd4224a3860ca894c5'),
                new Parameter('client_secret', 'string', '8897b24436ac63e457fbd7d0bd5b678686c0cb214ef92fa9e8464fc777ec5'),
                new Parameter('redirect_uri', 'string', 'https://example.com/oauth/callback'),
                new Parameter('code', 'string', 'e7f8c62113a47f7a5a9dca1f'),
                new Parameter('refresh_token', 'string', 'f7a5a9dca1fe7f8c62113a47'),
            ]
        );
    }
}
