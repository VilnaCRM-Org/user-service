<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\Factory\Response\UserConfirmedFactory;
use App\Tests\Unit\UnitTestCase;

final class UserConfirmedResponseFactoryTest extends UnitTestCase
{
    public function testGetResponse(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);

        $factory = new UserConfirmedFactory($responseBuilder);

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
