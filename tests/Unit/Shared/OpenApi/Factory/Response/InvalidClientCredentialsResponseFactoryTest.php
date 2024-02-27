<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\Factory\Response\InvalidCredentialsFactory;
use App\Tests\Unit\UnitTestCase;

class InvalidClientCredentialsResponseFactoryTest extends UnitTestCase
{
    public function testGetResponse(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);

        $factory = new InvalidCredentialsFactory($responseBuilder);

        $errorParam = new Parameter('error', 'string', 'invalid_client');
        $errorDescriptionParam = new Parameter('error_description', 'string', 'Client authentication failed');
        $messageParam = new Parameter('message', 'string', 'Client authentication failed');

        $responseBuilder->expects($this->once())
            ->method('build')
            ->with(
                'Invalid client credentials',
                [$errorParam, $errorDescriptionParam, $messageParam],
                []
            )
            ->willReturn(new Response());

        $response = $factory->getResponse();
        $this->assertInstanceOf(Response::class, $response);
    }
}
