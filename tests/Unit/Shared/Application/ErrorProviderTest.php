<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\State\ApiResource\Error;
use App\Shared\Application\ErrorProvider;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\DomainException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class ErrorProviderTest extends UnitTestCase
{
    private TranslatorInterface $translator;
    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
    }

    public function testProvide(): void
    {
        $operation = $this->createMock(HttpOperation::class);
        $status = $this->faker->numberBetween(200, 499);
        $operation->expects($this->once())->
            method('getStatus')->willReturn($status);

        $errorText = $this->faker->word();

        $exception = new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, $errorText);
        $request = new Request();
        $request->attributes->set('exception', $exception);
        $context = ['request' => $request];

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('error.internal')
            ->willReturn($this->faker->word());

        $errorProvider = new ErrorProvider($this->translator);

        $error = $errorProvider->provide($operation, [], $context);

        $this->assertInstanceOf(Error::class, $error);

        $this->assertEquals($status, $error->getStatusCode());

        $this->assertEquals($errorText, $error->getDetail());
    }

    public function testProvideWithoutErrorCode(): void
    {
        $operation = $this->createMock(HttpOperation::class);
        $operation->expects($this->once())->
        method('getStatus')->willReturn(null);

        $exception = new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, $this->faker->word());
        $request = new Request();
        $request->attributes->set('exception', $exception);
        $context = ['request' => $request];

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('error.internal')
            ->willReturn('Something went wrong');

        $errorProvider = new ErrorProvider($this->translator);

        $error = $errorProvider->provide($operation, [], $context);

        $this->assertInstanceOf(Error::class, $error);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $error->getStatusCode());

        $this->assertEquals('Something went wrong', $error->getDetail());
    }

    public function testProvideDomainException(): void
    {
        $operation = $this->createMock(HttpOperation::class);
        $status = $this->faker->numberBetween(200, 500);
        $operation->expects($this->once())->
        method('getStatus')->willReturn($status);
        $template = $this->faker->word();
        $args = [];
        $errorText = $this->faker->word();

        $exception = new class($template, $args) extends DomainException {
            public function __construct(
                private string $template,
                private array $args
            ) {
                parent::__construct();
            }

            public function getTranslationTemplate(): string
            {
                return $this->template;
            }

            public function getTranslationArgs(): array
            {
                return $this->args;
            }
        };

        $this->translator
            ->method('trans')
            ->withConsecutive(['error.internal'], [$template, $args])
            ->willReturnOnConsecutiveCalls('', $errorText);

        $request = new Request();
        $request->attributes->set('exception', $exception);
        $context = ['request' => $request];

        $errorProvider = new ErrorProvider($this->translator);

        $error = $errorProvider->provide($operation, [], $context);

        $this->assertInstanceOf(Error::class, $error);

        $this->assertEquals($status, $error->getStatusCode());

        $this->assertEquals($errorText, $error->getDetail());
    }

    public function testProvideGraphQLInternalError(): void
    {
        $operation = $this->createMock(HttpOperation::class);
        $errorText = $this->faker->word();

        $exception = new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, $this->faker->word());

        $request = Request::create('graphql');
        ;
        $request->attributes->set('exception', $exception);

        $context = ['request' => $request];

        $this->translator
            ->method('trans')
            ->with('error.internal')
            ->willReturn($errorText);

        $errorProvider = new ErrorProvider($this->translator);

        $error = $errorProvider->provide($operation, [], $context);

        $this->assertEquals($errorText, $error['message']);
    }
}
