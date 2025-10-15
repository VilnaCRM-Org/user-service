<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;
use App\Shared\Application\OpenApi\Builder\Requirement;
use App\Shared\Application\OpenApi\Factory\Request\UpdateUserRequestFactory;
use App\Tests\Unit\UnitTestCase;

final class UpdateUserRequestFactoryTest extends UnitTestCase
{
    public function testGetRequest(): void
    {
        $requestBuilder = $this->createMock(RequestBuilder::class);

        $factory = new UpdateUserRequestFactory($requestBuilder);

        $requestBuilder->expects($this->once())
            ->method('build')
            ->with(
                $this->getParams()
            )
            ->willReturn(new RequestBody());

        $request = $factory->getRequest();

        $this->assertInstanceOf(RequestBody::class, $request);
    }

    /**
     * @return array<Parameter>
     */
    private function getParams(): array
    {
        return [
            $this->getEmailParam(),
            $this->getInitialsParam(),
            $this->getOldPasswordParam(),
            $this->getNewPasswordParam(),
        ];
    }

    private function getEmailParam(): Parameter
    {
        return new Parameter(
            'email',
            'string',
            'update-user@example.com',
            255,
            'email',
            Requirement::OPTIONAL
        );
    }

    private function getInitialsParam(): Parameter
    {
        return new Parameter(
            'initials',
            'string',
            'UpdateUser',
            255,
            null,
            Requirement::OPTIONAL,
            '^\\S+$'
        );
    }

    private function getOldPasswordParam(): Parameter
    {
        return new Parameter(
            'oldPassword',
            'string',
            'Password1!',
            64,
            null,
            Requirement::REQUIRED,
            '^(?=.*[0-9])(?=.*[A-Z]).{8,64}$'
        );
    }

    private function getNewPasswordParam(): Parameter
    {
        return new Parameter(
            'newPassword',
            'string',
            'Password1!',
            64,
            null,
            Requirement::OPTIONAL,
            '^(?=.*[0-9])(?=.*[A-Z]).{8,64}$'
        );
    }
}
