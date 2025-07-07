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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ErrorProviderTest extends UnitTestCase
{
    private TranslatorInterface $translator;
    private HttpOperation $operation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->operation = $this->createMock(HttpOperation::class);
    }

    public function testProvide(): void
    {
        $status = $this->faker->numberBetween(200, 499);
        $this->operation->expects($this->once())
            ->method('getStatus')->willReturn($status);

        $errorText = $this->faker->word();

        $exception = new HttpException(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $errorText
        );
        $request = new Request(attributes: ['exception' => $exception]);
        $context = ['request' => $request];

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('error.internal')
            ->willReturn($this->faker->word());

        $errorProvider = new ErrorProvider($this->translator);

        $error = $errorProvider->provide($this->operation, [], $context);

        $this->assertInstanceOf(Error::class, $error);

        $this->assertEquals($status, $error->getStatusCode());

        $this->assertEquals($errorText, $error->getDetail());
    }

    public function testProvideWithoutErrorCode(): void
    {
        $this->operation->expects($this->once())
            ->method('getStatus')->willReturn(null);

        $errorText = $this->faker->word();

        $exception = new HttpException(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $this->faker->word()
        );
        $request = new Request(attributes: ['exception' => $exception]);
        $context = ['request' => $request];

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('error.internal')->willReturn($errorText);

        $errorProvider = new ErrorProvider($this->translator);
        $error = $errorProvider->provide($this->operation, [], $context);

        $this->assertInstanceOf(Error::class, $error);

        $this->assertEquals(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $error->getStatusCode()
        );

        $this->assertEquals($errorText, $error->getDetail());
    }

    public function testProvideNotFoundException(): void
    {
        $status = Response::HTTP_NOT_FOUND;
        $this->operation->expects($this->once())
            ->method('getStatus')->willReturn($status);

        $errorText = $this->faker->word();

        $exception = new NotFoundHttpException();
        $request = new Request(attributes: ['exception' => $exception]);
        $context = ['request' => $request];

        $this->translator
            ->method('trans')
            ->withConsecutive(['error.internal'], ['error.not.found.http'])
            ->willReturnOnConsecutiveCalls('', $errorText);

        $errorProvider = new ErrorProvider($this->translator);

        $error = $errorProvider->provide($this->operation, [], $context);

        $this->assertInstanceOf(Error::class, $error);

        $this->assertEquals($status, $error->getStatusCode());

        $this->assertEquals($errorText, $error->getDetail());
    }

    public function testProvideDomainException(): void
    {
        $status = $this->faker->numberBetween(200, 500);
        $this->operation->expects($this->once())
            ->method('getStatus')->willReturn($status);
        $template = $this->faker->word();
        $args = [];
        $errorText = $this->faker->word();

        $this->translator
            ->expects($this->exactly(2))
            ->method('trans')
            ->withConsecutive(['error.internal'], [$template, $args])
            ->willReturnOnConsecutiveCalls('', $errorText);

        $exception = $this->getDomainException($template, $args);

        $request = new Request();
        $request->attributes->set('exception', $exception);
        $context = ['request' => $request];

        $errorProvider = new ErrorProvider($this->translator);

        $error = $errorProvider->provide($this->operation, [], $context);

        $this->assertInstanceOf(Error::class, $error);

        $this->assertEquals($status, $error->getStatusCode());

        $this->assertEquals($errorText, $error->getDetail());
    }

    public function testProvideGraphQLInternalError(): void
    {
        $errorText = $this->faker->word();

        $exception = new HttpException(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $this->faker->word()
        );

        $request = Request::create('graphql');

        $request->attributes->set('exception', $exception);

        $context = ['request' => $request];

        $this->translator
            ->method('trans')
            ->with('error.internal')
            ->willReturn($errorText);

        $errorProvider = new ErrorProvider($this->translator);

        $error = $errorProvider->provide($this->operation, [], $context);

        $this->assertEquals($errorText, $error['message']);
    }

    /**
     * @param array<string> $args
     */
    private function getDomainException(
        string $template,
        array $args
    ): DomainException {
        return new class($template, $args) extends DomainException {
            /**
             * @param array<string> $args
             */
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

            /**
             * @return array<string>
             */
            public function getTranslationArgs(): array
            {
                return $this->args;
            }
        };
    }
}
