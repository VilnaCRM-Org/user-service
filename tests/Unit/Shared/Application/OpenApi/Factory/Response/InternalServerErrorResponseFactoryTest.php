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

        $responseBuilder->expects($this->once())
            ->method('build')
            ->with(
                'Internal server error',
                $this->getParams()
            )
            ->willReturn(new Response());

        $response = $factory->getResponse();
        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @return array<Parameter>
     */
    private function getParams(): array
    {
        $typeParam = $this->getTypeParam();
        $titleParam = $this->getTitleParam();
        $detailParam = $this->getDetailParam();
        $statusParam = $this->getStatusParam();

        return [$typeParam, $titleParam, $detailParam, $statusParam];
    }

    private function getTypeParam(): Parameter
    {
        return new Parameter(
            'type',
            'string',
            '/errors/500'
        );
    }

    private function getTitleParam(): Parameter
    {
        return new Parameter(
            'title',
            'string',
            'An error occurred'
        );
    }

    private function getDetailParam(): Parameter
    {
        return new Parameter(
            'detail',
            'string',
            'Something went wrong'
        );
    }

    private function getStatusParam(): Parameter
    {
        return new Parameter(
            'status',
            'integer',
            500
        );
    }
}
