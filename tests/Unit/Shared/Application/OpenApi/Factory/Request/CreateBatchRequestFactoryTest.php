<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;
use App\Shared\Application\OpenApi\Factory\Request\CreateBatchRequestFactory;
use App\Tests\Unit\UnitTestCase;

final class CreateBatchRequestFactoryTest extends UnitTestCase
{
    private RequestBuilder $requestBuilder;
    private CreateBatchRequestFactory $factory;

    protected function setUp(): void
    {
        $this->requestBuilder = $this->createMock(RequestBuilder::class);
        $this->factory = new CreateBatchRequestFactory($this->requestBuilder);
    }

    public function testGetRequest(): void
    {
        $expectedParameters = [
            new Parameter(
                'users',
                'array',
                [
                    [
                        'email' => 'user@example.com',
                        'initials' => 'Name Surname',
                        'password' => 'passWORD1',
                    ],
                ],
            ),
        ];

        $expectedRequestBody = $this->createMock(RequestBody::class);

        $this->requestBuilder->expects($this->once())
            ->method('build')
            ->with($expectedParameters)
            ->willReturn($expectedRequestBody);

        $requestBody = $this->factory->getRequest();
        $this->assertSame($expectedRequestBody, $requestBody);
    }
}
