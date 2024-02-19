<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\OpenApi\Builder\ResponseBuilder;
use App\Shared\OpenApi\Factory\Response\UserConfirmedResponseFactory;
use App\Tests\Unit\UnitTestCase;

class UserConfirmedResponseFactoryTest extends UnitTestCase
{
    public function testGetResponse(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);

        $factory = new UserConfirmedResponseFactory($responseBuilder);

        $responseBuilder->expects($this->once())
            ->method('build')
            ->with(
                'User confirmed',
                [],
                []
            )
            ->willReturn($this->createStub(Response::class));

        $factory->getResponse();
    }
}
