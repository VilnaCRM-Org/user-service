<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\OpenApi\Builder\ResponseBuilder;
use App\Shared\OpenApi\Factory\Response\TokenNotFoundResponseFactory;
use App\Tests\Unit\UnitTestCase;

class TokenNotFoundResponseFactoryTest extends UnitTestCase
{
    public function testGetResponse(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);

        $factory = new TokenNotFoundResponseFactory($responseBuilder);

        $responseBuilder->expects($this->once())
            ->method('build')
            ->with(
                'Token not found or expired',
                [],
                []
            )
            ->willReturn($this->createStub(Response::class));

        $factory->getResponse();
    }
}
