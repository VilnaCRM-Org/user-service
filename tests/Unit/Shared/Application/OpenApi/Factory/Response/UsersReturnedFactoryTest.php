<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\ArrayResponseBuilder;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Factory\Response\UsersReturnedFactory;
use App\Tests\Unit\UnitTestCase;

final class UsersReturnedFactoryTest extends UnitTestCase
{
    public function testGetResponse(): void
    {
        $responseBuilder = $this->createMock(ArrayResponseBuilder::class);

        $factory = new UsersReturnedFactory($responseBuilder);

        $responseBuilder->expects($this->once())
            ->method('build')
            ->with(
                'Users returned',
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
            'user@example.com'
        );
    }

    private function getInitialsParam(): Parameter
    {
        return new Parameter(
            'initials',
            'string',
            'NameSurname'
        );
    }

    private function getIdParam(): Parameter
    {
        return new Parameter(
            'id',
            'string',
            '018dd6ba-e901-7a8c-b27d-65d122caca6b'
        );
    }
}
