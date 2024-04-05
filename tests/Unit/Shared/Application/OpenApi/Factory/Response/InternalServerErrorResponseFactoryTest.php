<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\Factory\Response\InternalErrorFactory;
use App\Tests\Unit\UnitTestCase;

final class InternalServerErrorResponseFactoryTest extends UnitTestCase
{
    public function testGetResponse(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);

        $factory = new InternalErrorFactory($responseBuilder);

        $typeParam = new Parameter(
            'type',
            'string',
            '/errors/500'
        );
        $titleParam = new Parameter(
            'title',
            'string',
            'An error occurred'
        );
        $detailParam = new Parameter(
            'detail',
            'string',
            'Something went wrong'
        );
        $statusParam = new Parameter(
            'status',
            'integer',
            500
        );

        $responseBuilder->expects($this->once())
            ->method('build')
            ->with(
                'Internal server error',
                [$typeParam, $titleParam, $detailParam, $statusParam]
            )
            ->willReturn(new Response());

        $response = $factory->getResponse();
        $this->assertInstanceOf(Response::class, $response);
    }
}
