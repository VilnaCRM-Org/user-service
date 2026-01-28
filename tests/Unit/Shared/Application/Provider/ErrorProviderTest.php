<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Provider;

use ApiPlatform\Metadata\Exception\HttpExceptionInterface as ApiPlatformHttpExceptionInterface;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\State\ApiResource\Error;
use App\Shared\Application\Provider\ErrorProvider;
use App\Shared\Application\Resolver\HttpExceptionDetailResolver;
use App\Shared\Application\Resolver\HttpExceptionHeadersResolver;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\DomainException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ErrorProviderTest extends UnitTestCase
{
    private TranslatorInterface $translator;
    private HttpOperation $operation;
    private RequestStack $requestStack;
    private int $status;
    private string $template;
    /** @var array<string> */
    private array $args;
    private string $errorText;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->operation = $this->createMock(HttpOperation::class);
        $this->requestStack = new RequestStack();
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

        $errorProvider = $this->errorProvider();

        $error = $errorProvider->provide($this->operation, [], $context);

        $this->assertInstanceOf(Error::class, $error);

        $this->assertEquals($status, $error->getStatusCode());

        $this->assertEquals($errorText, $error->getDetail());
    }

    public function testProvideWithoutErrorCode(): void
    {
        $this->operation->expects($this->once())
            ->method('getStatus')->willReturn(null);

        $errorText = $this->faker->unique()->word();
        $exceptionMessage = $this->faker->unique()->word();

        $exception = new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, $exceptionMessage);
        $request = new Request(attributes: ['exception' => $exception]);
        $context = ['request' => $request];

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('error.internal')->willReturn($errorText);

        $errorProvider = $this->errorProvider();
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
            ->willReturnCallback(
                $this->expectSequential(
                    [['error.internal'], ['error.not.found.http']],
                    ['', $errorText]
                )
            );

        $errorProvider = $this->errorProvider();

        $error = $errorProvider->provide($this->operation, [], $context);

        $this->assertInstanceOf(Error::class, $error);

        $this->assertEquals($status, $error->getStatusCode());

        $this->assertEquals($errorText, $error->getDetail());
    }

    public function testProvideDomainException(): void
    {
        $this->setUpDomainExceptionMocks();
        $exception = $this->getDomainException($this->template, $this->args);
        $context = $this->createDomainExceptionContext($exception);
        $errorProvider = $this->errorProvider();

        $error = $errorProvider->provide($this->operation, [], $context);

        $this->assertInstanceOf(Error::class, $error);
        $this->assertEquals($this->status, $error->getStatusCode());
        $this->assertEquals($this->errorText, $error->getDetail());
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

        $errorProvider = $this->errorProvider();

        $error = $errorProvider->provide($this->operation, [], $context);

        $this->assertEquals($errorText, $error['message']);
    }

    public function testApiPlatformHttpExceptionHeadersArePreserved(): void
    {
        $this->setUpApiPlatformHttpExceptionMocks();
        $exception = $this->createApiPlatformHttpException();
        $context = ['request' => new Request(attributes: ['exception' => $exception])];
        $errorProvider = $this->errorProvider();

        $error = $errorProvider->provide($this->operation, [], $context);

        $this->assertInstanceOf(Error::class, $error);
        $this->assertSame(['X-Debug' => '1'], $error->getHeaders());
    }

    public function testSymfonyHttpExceptionHeadersArePreserved(): void
    {
        $this->operation->expects($this->once())
            ->method('getStatus')
            ->willReturn(Response::HTTP_BAD_REQUEST);

        $exception = new HttpException(
            Response::HTTP_BAD_REQUEST,
            'Invalid request',
            null,
            ['Retry-After' => '30']
        );

        $request = new Request(attributes: ['exception' => $exception]);
        $context = ['request' => $request];

        $this->translator
            ->method('trans')
            ->with('error.internal')
            ->willReturn('');

        $errorProvider = $this->errorProvider();

        $error = $errorProvider->provide($this->operation, [], $context);

        $this->assertInstanceOf(Error::class, $error);
        $this->assertSame(['Retry-After' => '30'], $error->getHeaders());
    }

    public function testProvideWhenRequestIsMissing(): void
    {
        $status = $this->faker->numberBetween(400, 599);
        $internalError = $this->faker->word();

        $this->operation
            ->method('getStatus')
            ->willReturn($status);

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('error.internal')
            ->willReturn($internalError);

        $errorProvider = $this->errorProvider();

        $error = $errorProvider->provide($this->operation);

        $this->assertInstanceOf(Error::class, $error);
        $this->assertSame($status, $error->getStatusCode());
        $this->assertSame($internalError, $error->getDetail());
    }

    public function testContextRequestTakesPrecedenceOverRequestStack(): void
    {
        $status = Response::HTTP_BAD_REQUEST;
        $contextErrorMessage = 'Error from context request';
        $stackErrorMessage = 'Error from stack request';

        $this->operation->method('getStatus')->willReturn($status);

        $context = $this->setupRequestsWithDifferentExceptions(
            $status,
            $contextErrorMessage,
            $stackErrorMessage
        );

        $this->translator->method('trans')->with('error.internal')->willReturn('');

        $errorProvider = $this->errorProvider();
        $error = $errorProvider->provide($this->operation, [], $context);

        $this->assertContextRequestTakesPrecedence(
            $error,
            $contextErrorMessage,
            $stackErrorMessage
        );
    }

    /** @return array<string, Request> */
    private function setupRequestsWithDifferentExceptions(
        int $status,
        string $contextErrorMessage,
        string $stackErrorMessage
    ): array {
        $contextException = new HttpException($status, $contextErrorMessage);
        $stackException = new HttpException($status, $stackErrorMessage);

        $contextRequest = new Request(attributes: ['exception' => $contextException]);
        $stackRequest = new Request(attributes: ['exception' => $stackException]);

        $this->requestStack->push($stackRequest);
        self::assertSame($stackRequest, $this->requestStack->getCurrentRequest());

        return ['request' => $contextRequest];
    }

    private function assertContextRequestTakesPrecedence(
        Error $error,
        string $contextErrorMessage,
        string $stackErrorMessage
    ): void {
        $this->assertInstanceOf(Error::class, $error);
        $this->assertSame($contextErrorMessage, $error->getDetail());
        $this->assertNotSame($stackErrorMessage, $error->getDetail());
    }

    private function setUpDomainExceptionMocks(): void
    {
        $this->status = Response::HTTP_BAD_REQUEST;
        $this->template = $this->faker->word();
        $this->args = [];
        $this->errorText = $this->faker->word();

        $this->operation->expects($this->once())
            ->method('getStatus')->willReturn($this->status);

        $this->translator
            ->expects($this->exactly(2))
            ->method('trans')
            ->willReturnCallback(
                $this->expectSequential(
                    [['error.internal'], [$this->template, $this->args]],
                    ['', $this->errorText]
                )
            );
    }

    /**
     * @return array{request: Request}
     */
    private function createDomainExceptionContext(\Throwable $exception): array
    {
        $request = new Request();
        $request->attributes->set('exception', $exception);
        return ['request' => $request];
    }

    private function setUpApiPlatformHttpExceptionMocks(): void
    {
        $this->operation->expects($this->once())
            ->method('getStatus')
            ->willReturn(Response::HTTP_BAD_REQUEST);

        $this->translator
            ->method('trans')
            ->with('error.internal')
            ->willReturn('');
    }

    private function createApiPlatformHttpException(): ApiPlatformHttpExceptionInterface
    {
        return new class() extends \RuntimeException implements ApiPlatformHttpExceptionInterface {
            public function __construct()
            {
                parent::__construct('Invalid payload');
            }

            #[\Override]
            public function getStatusCode(): int
            {
                return Response::HTTP_BAD_REQUEST;
            }

            /** @return array<string, string> */
            #[\Override]
            public function getHeaders(): array
            {
                return ['X-Debug' => '1'];
            }
        };
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

            #[\Override]
            public function getTranslationTemplate(): string
            {
                return $this->template;
            }

            /**
             * @return array<string>
             */
            #[\Override]
            public function getTranslationArgs(): array
            {
                return $this->args;
            }
        };
    }

    private function errorProvider(): ErrorProvider
    {
        return new ErrorProvider(
            $this->translator,
            $this->requestStack,
            new HttpExceptionHeadersResolver(),
            new HttpExceptionDetailResolver($this->translator)
        );
    }
}
