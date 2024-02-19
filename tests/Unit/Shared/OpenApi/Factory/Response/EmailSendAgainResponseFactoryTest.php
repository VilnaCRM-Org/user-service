<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\OpenApi\Builder\ResponseBuilder;
use App\Shared\OpenApi\Factory\Response\EmailSendAgainResponseFactory;
use App\Tests\Unit\UnitTestCase;

class EmailSendAgainResponseFactoryTest extends UnitTestCase
{
    public function testGetResponse(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);

        $factory = new EmailSendAgainResponseFactory($responseBuilder);

        $responseBuilder->expects($this->once())
            ->method('build')
            ->with('Email was send again')
            ->willReturn(new Response());

        $response = $factory->getResponse();
        $this->assertInstanceOf(Response::class, $response);
    }
}
