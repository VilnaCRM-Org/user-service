<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;
use App\Shared\Application\OpenApi\Factory\Request\OAuthTokenRequestFactory;
use App\Tests\Unit\UnitTestCase;

final class OAuthTokenRequestFactoryTest extends UnitTestCase
{
    public function testGetRequest(): void
    {
        $requestBuilder = $this->createMock(RequestBuilder::class);

        $factory = new OAuthTokenRequestFactory($requestBuilder);

        $requestBuilder->expects($this->once())
            ->method('build')
            ->with(
                $this->getParams()
            )
            ->willReturn(new RequestBody());

        $request = $factory->getRequest();

        $this->assertInstanceOf(RequestBody::class, $request);
    }

    /**
     * @return array<Parameter>
     */
    private function getParams(): array
    {
        $grantTypeParam = $this->getGrantTypeParam();
        $clientIdParam = $this->getClientIdParam();
        $clientSecretParam = $this->getClientSecretParam();
        $redirectUriParam = $this->getRedirectUriParam();
        $codeParam = $this->getCodeParam();
        $refreshTokenParam = $this->getRefreshTokenParam();

        return [
            $grantTypeParam,
            $clientIdParam,
            $clientSecretParam,
            $redirectUriParam,
            $codeParam,
            $refreshTokenParam,
        ];
    }

    private function getGrantTypeParam(): Parameter
    {
        return new Parameter(
            'grant_type',
            'string',
            'authorization_code'
        );
    }

    private function getClientIdParam(): Parameter
    {
        return new Parameter(
            'client_id',
            'string',
            'dc0bc6323f16fecd4224a3860ca894c5'
        );
    }

    private function getClientSecretParam(): Parameter
    {
        return new Parameter(
            'client_secret',
            'string',
            '8897b24436ac63e457fbd7d0bd5b678686c0cb214ef92fa9e8464fc7'
        );
    }

    private function getRedirectUriParam(): Parameter
    {
        return new Parameter(
            'redirect_uri',
            'string',
            'https://example.com/oauth/callback'
        );
    }

    private function getCodeParam(): Parameter
    {
        return new Parameter(
            'code',
            'string',
            'e7f8c62113a47f7a5a9dca1f'
        );
    }

    private function getRefreshTokenParam(): Parameter
    {
        return new Parameter(
            'refresh_token',
            'string',
            'f7a5a9dca1fe7f8c62113a47'
        );
    }
}
