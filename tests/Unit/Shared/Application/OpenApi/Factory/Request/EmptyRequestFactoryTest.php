<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;
use App\Shared\Application\OpenApi\Factory\Request\EmptyRequestFactory;
use App\Tests\Unit\UnitTestCase;

final class EmptyRequestFactoryTest extends UnitTestCase
{
    private EmptyRequestFactory $factory;
    private RequestBuilder $builderMock;

    protected function setUp(): void
    {
        $this->builderMock = $this->createMock(RequestBuilder::class);
        $this->factory = new EmptyRequestFactory($this->builderMock);
    }

    public function testGetRequest(): void
    {
        $this->builderMock->expects($this->once())
            ->method('build')
            ->willReturn(new RequestBody());

        $request = $this->factory->getRequest();

        $this->assertInstanceOf(RequestBody::class, $request);
    }
}
