<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\State\ApiResource\Error;
use App\Shared\Infrastructure\ErrorProvider;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ErrorProviderTest extends UnitTestCase
{
    public function testProvide(): void
    {
        $operation = $this->createMock(HttpOperation::class);
        $operation->expects($this->once())->
            method('getStatus')->willReturn(500);

        $exception = new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Test exception');
        $request = new Request();
        $request->attributes->set('exception', $exception);
        $context = ['request' => $request];

        $errorProvider = new ErrorProvider();

        $error = $errorProvider->provide($operation, [], $context);

        $this->assertInstanceOf(Error::class, $error);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $error->getStatusCode());

        $this->assertEquals('Something went wrong', $error->getDetail());
    }

    public function testProvideWithoutErrorCode(): void
    {
        $operation = $this->createMock(HttpOperation::class);
        $operation->expects($this->once())->
        method('getStatus')->willReturn(null);

        $exception = new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Test exception');
        $request = new Request();
        $request->attributes->set('exception', $exception);
        $context = ['request' => $request];

        $errorProvider = new ErrorProvider();

        $error = $errorProvider->provide($operation, [], $context);

        $this->assertInstanceOf(Error::class, $error);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $error->getStatusCode());

        $this->assertEquals('Something went wrong', $error->getDetail());
    }
}
