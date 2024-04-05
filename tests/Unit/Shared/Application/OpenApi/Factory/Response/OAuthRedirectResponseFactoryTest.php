<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\Header;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\Factory\Response\OAuthRedirectFactory;
use App\Tests\Unit\UnitTestCase;

final class OAuthRedirectResponseFactoryTest extends UnitTestCase
{
    public function testGetResponse(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);

        $factory = new OAuthRedirectFactory($responseBuilder);

        $locationHeader = new Header(
            'Location',
            'The URI to redirect to for user authorization',
            'string',
            'uri',
            'https://example.com/oauth/callback?code=e7f8c62113a4'
        );

        $responseBuilder->expects($this->once())
            ->method('build')
            ->with(
                'Redirect to the provided '.
                'redirect URI with authorization code.',
                [],
                [$locationHeader]
            )
            ->willReturn(new Response());

        $response = $factory->getResponse();
        $this->assertInstanceOf(Response::class, $response);
    }
}
