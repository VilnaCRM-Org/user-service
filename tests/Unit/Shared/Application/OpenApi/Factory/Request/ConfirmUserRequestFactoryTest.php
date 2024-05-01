<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;
use App\Shared\Application\OpenApi\Factory\Request\ConfirmUserRequestFactory;
use App\Tests\Unit\UnitTestCase;

final class ConfirmUserRequestFactoryTest extends UnitTestCase
{
    public function testGetRequest(): void
    {
        $requestBuilder = $this->createMock(RequestBuilder::class);

        $factory = new ConfirmUserRequestFactory($requestBuilder);

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
            $this->getTokenParam(),
        ];
    }

    private function getTokenParam(): Parameter
    {
        return new Parameter(
            'token',
            'string',
            'token'
        );
    }
}
