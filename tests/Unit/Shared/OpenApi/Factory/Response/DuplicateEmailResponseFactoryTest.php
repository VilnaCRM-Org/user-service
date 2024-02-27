<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\Factory\Response\DuplicateEmailFactory;
use App\Tests\Unit\UnitTestCase;

class DuplicateEmailResponseFactoryTest extends UnitTestCase
{
    public function testGetResponse(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);

        $factory = new DuplicateEmailFactory($responseBuilder);

        $typeParam = new Parameter('type', 'string', 'https://tools.ietf.org/html/rfc2616#section-10');
        $titleParam = new Parameter('title', 'string', 'An error occurred');
        $detailParam = new Parameter('detail', 'string', 'user@example.com address is already registered. Please use a different email address or try logging in.');
        $statusParam = new Parameter('status', 'integer', 409);

        $responseBuilder->expects($this->once())
            ->method('build')
            ->with(
                'Duplicate email',
                [$typeParam, $titleParam, $detailParam, $statusParam]
            )
            ->willReturn(new Response());

        $response = $factory->getResponse();
        $this->assertInstanceOf(Response::class, $response);
    }
}
