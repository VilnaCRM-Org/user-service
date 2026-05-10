<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\Factory\Response\UnsupportedMediaTypeFactory;
use App\Shared\Application\OpenApi\ValueObject\Parameter;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class UnsupportedMediaTypeResponseFactoryTest extends UnitTestCase
{
    public function testGetResponse(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);
        $factory = new UnsupportedMediaTypeFactory($responseBuilder);

        $responseBuilder->expects($this->once())
            ->method('build')
            ->with(
                'Unsupported media type',
                [
                    new Parameter('type', 'string', '/errors/415'),
                    new Parameter('title', 'string', 'Unsupported Media Type'),
                    new Parameter('detail', 'string', 'The "Content-Type" header must exist.'),
                    new Parameter('status', 'integer', HttpResponse::HTTP_UNSUPPORTED_MEDIA_TYPE),
                ],
                [],
                'application/problem+json'
            )
            ->willReturn($this->createStub(Response::class));

        $factory->getResponse();
    }
}
