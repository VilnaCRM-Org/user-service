<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;
use App\Shared\Application\OpenApi\Factory\Request\ReplaceUserRequestFactory;
use App\Tests\Unit\UnitTestCase;

final class ReplaceUserRequestFactoryTest extends UnitTestCase
{
    public function testGetRequest(): void
    {
        $requestBuilder = $this->createMock(RequestBuilder::class);

        $factory = new ReplaceUserRequestFactory($requestBuilder);

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
            'user@example.com',
            255,
            'email'
        );
    }

    private function getInitialsParam(): Parameter
    {
        return new Parameter(
            'initials',
            'string',
            'Name Surname',
            255
        );
    }

    private function getOldPasswordParam(): Parameter
    {
        return new Parameter(
            'oldPassword',
            'string',
            'passWORD1',
            255
        );
    }

    private function getNewPasswordParam(): Parameter
    {
        return new Parameter(
            'newPassword',
            'string',
            'PASSword2',
            255
        );
    }
}
