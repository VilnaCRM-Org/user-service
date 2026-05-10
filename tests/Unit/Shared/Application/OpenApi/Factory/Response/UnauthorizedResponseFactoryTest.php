<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\Factory\Response\UnauthorizedResponseFactory;
use App\Shared\Application\OpenApi\ValueObject\Parameter;
use App\Tests\Unit\UnitTestCase;

final class UnauthorizedResponseFactoryTest extends UnitTestCase
{
    public function testGetResponse(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);
        $factory = new UnauthorizedResponseFactory($responseBuilder);

        $responseBuilder->expects($this->once())
            ->method('build')
            ->with(
                'Unauthorized',
                $this->getParams(),
                [],
                'application/problem+json'
            )
            ->willReturn(new Response());

        $response = $factory->getResponse();

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @return array<Parameter>
     *
     * @psalm-return list{Parameter, Parameter, Parameter, Parameter}
     */
    private function getParams(): array
    {
        return [
            $this->getTypeParam(),
            $this->getTitleParam(),
            $this->getStatusParam(),
            $this->getDetailParam(),
        ];
    }

    private function getTypeParam(): Parameter
    {
        return new Parameter(
            'type',
            'string',
            'about:blank'
        );
    }

    private function getTitleParam(): Parameter
    {
        return new Parameter(
            'title',
            'string',
            'Unauthorized'
        );
    }

    private function getStatusParam(): Parameter
    {
        return new Parameter(
            'status',
            'integer',
            401
        );
    }

    private function getDetailParam(): Parameter
    {
        return new Parameter(
            'detail',
            'string',
            'Authentication required.'
        );
    }
}
