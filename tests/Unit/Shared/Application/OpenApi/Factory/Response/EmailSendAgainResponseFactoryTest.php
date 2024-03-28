<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\Factory\Response\EmailSendFactory;
use App\Tests\Unit\UnitTestCase;

class EmailSendAgainResponseFactoryTest extends UnitTestCase
{
    public function testGetResponse(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);

        $factory = new EmailSendFactory($responseBuilder);

        $responseBuilder->expects($this->once())
            ->method('build')
            ->with('Email was send again')
            ->willReturn(new Response());

        $response = $factory->getResponse();
        $this->assertInstanceOf(Response::class, $response);
    }
}
