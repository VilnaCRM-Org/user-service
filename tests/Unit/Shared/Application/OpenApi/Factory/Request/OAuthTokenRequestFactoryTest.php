<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;
use App\Shared\Application\OpenApi\Factory\Request\OAuthTokenRequestFactory;
use App\Tests\Unit\UnitTestCase;

class OAuthTokenRequestFactoryTest extends UnitTestCase
{
    public function testGetRequest(): void
    {
        $requestBuilder = $this->createMock(RequestBuilder::class);

        $factory = new OAuthTokenRequestFactory($requestBuilder);

        $grantTypeParam = new Parameter('grant_type', 'string', 'authorization_code');
        $clientIdParam = new Parameter('client_id', 'string', 'dc0bc6323f16fecd4224a3860ca894c5');
        $clientSecretParam = new Parameter('client_secret', 'string', '8897b24436ac63e457fbd7d0bd5b678686c0cb214ef92fa9e8464fc7');
        $redirectUriParam = new Parameter('redirect_uri', 'string', 'https://example.com/oauth/callback');
        $codeParam = new Parameter('code', 'string', 'e7f8c62113a47f7a5a9dca1f');
        $refreshTokenParam = new Parameter('refresh_token', 'string', 'f7a5a9dca1fe7f8c62113a47');

        $requestBuilder->expects($this->once())
            ->method('build')
            ->with([$grantTypeParam, $clientIdParam, $clientSecretParam, $redirectUriParam, $codeParam, $refreshTokenParam])
            ->willReturn(new RequestBody());

        $request = $factory->getRequest();

        $this->assertInstanceOf(RequestBody::class, $request);
    }
}
