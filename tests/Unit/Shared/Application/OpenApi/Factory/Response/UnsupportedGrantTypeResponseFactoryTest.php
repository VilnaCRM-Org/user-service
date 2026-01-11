<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\Factory\Response\UnsupportedTypeFactory;
use App\Shared\Application\OpenApi\ValueObject\Parameter;
use App\Tests\Unit\UnitTestCase;

final class UnsupportedGrantTypeResponseFactoryTest extends UnitTestCase
{
    public function testGetResponse(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);

        $factory = new UnsupportedTypeFactory($responseBuilder);

        $responseBuilder->expects($this->once())
            ->method('build')
            ->with(
                'Unsupported grant type',
                [
                    $this->getErrorParam(),
                    $this->getErrorDescriptionParam(),
                    $this->getHintParam(),
                ],
                []
            )
            ->willReturn($this->createStub(Response::class));

        $factory->getResponse();
    }

    private function getErrorParam(): Parameter
    {
        return new Parameter(
            'error',
            'string',
            'unsupported_grant_type'
        );
    }

    private function getErrorDescriptionParam(): Parameter
    {
        return new Parameter(
            'error_description',
            'string',
            'The authorization grant type is '.
            'not supported by the authorization server.'
        );
    }

    private function getHintParam(): Parameter
    {
        return new Parameter(
            'hint',
            'string',
            'Check that all required parameters have been provided'
        );
    }
}
