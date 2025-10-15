<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\Factory\Response\UserCreatedResponseFactory;
use App\Tests\Unit\UnitTestCase;

final class UserCreatedResponseFactoryTest extends UnitTestCase
{
    public function testGetResponse(): void
    {
        $responseBuilder = $this->createMock(ResponseBuilder::class);

        $factory = new UserCreatedResponseFactory($responseBuilder);

        $responseBuilder->expects($this->once())
            ->method('build')
            ->with(
                'User created',
                [
                    $this->getConfirmedParam(),
                    $this->getEmailParam(),
                    $this->getInitialsParam(),
                    $this->getIdParam(),
                ],
                []
            )
            ->willReturn($this->createStub(Response::class));

        $factory->getResponse();
    }

    private function getConfirmedParam(): Parameter
    {
        return new Parameter(
            'confirmed',
            'boolean',
            false
        );
    }

    private function getEmailParam(): Parameter
    {
        return new Parameter(
            'email',
            'string',
            SchemathesisFixtures::CREATE_USER_EMAIL
        );
    }

    private function getInitialsParam(): Parameter
    {
        return new Parameter(
            'initials',
            'string',
            SchemathesisFixtures::CREATE_USER_INITIALS
        );
    }

    private function getIdParam(): Parameter
    {
        return new Parameter(
            'id',
            'string',
            SchemathesisFixtures::CREATE_USER_ID
        );
    }
}
