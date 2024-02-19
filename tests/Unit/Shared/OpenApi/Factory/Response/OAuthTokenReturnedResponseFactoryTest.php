<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\OpenApi\Builder\Parameter;
use App\Shared\OpenApi\Builder\ResponseBuilder;
use App\Shared\OpenApi\Factory\Response\OAuthTokenReturnedResponseFactory;
use App\Tests\Unit\UnitTestCase;

class OAuthTokenReturnedResponseFactoryTest extends UnitTestCase
{
    public function testGetResponse(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);

        $factory = new OAuthTokenReturnedResponseFactory($responseBuilder);

        $responseBuilder->expects($this->once())
            ->method('build')
            ->with(
                'Unsupported grant type',
                [
                    $this->getTypeParam(),
                    $this->getExpiresInParam(),
                    $this->getAccessTokenParam(),
                    $this->getRefreshTokenParam(),
                ],
                []
            )
            ->willReturn($this->createStub(Response::class));

        $factory->getResponse();
    }

    private function getTypeParam(): Parameter
    {
        return new Parameter(
            'token_type',
            'string',
            'Bearer'
        );
    }

    private function getExpiresInParam(): Parameter
    {
        return new Parameter('expires_in', 'integer', 3600);
    }

    private function getAccessTokenParam(): Parameter
    {
        return new Parameter(
            'access_token',
            'string',
            'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdW'
        );
    }

    private function getRefreshTokenParam(): Parameter
    {
        return new Parameter(
            'refresh_token',
            'string',
            'df9b4ae7ce2e1e8f2a3c1b4d'
        );
    }
}
