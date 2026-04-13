<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Runtime;

require_once __DIR__ . '/function-mock.php';

use App\Shared\Infrastructure\Runtime\FrankenPhpRunner;
use App\Shared\Infrastructure\Runtime\MockFrankenPhpFunctions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

interface TestKernelInterface extends HttpKernelInterface, TerminableInterface
{
}

final class FrankenPhpRunnerTest extends TestCase
{
    private array $originalServer = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalServer = $_SERVER;
        MockFrankenPhpFunctions::reset();
        $_SERVER = [
            'FOO' => 'bar',
            'HTTP_STALE_HEADER' => 'stale',
        ];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
        MockFrankenPhpFunctions::reset();

        parent::tearDown();
    }

    public function testRunPreservesRequestHeadersAndFiltersBootstrapHttpHeaders(): void
    {
        $kernel = $this->createMock(TestKernelInterface::class);
        $response = $this->createResponseMock(1);

        MockFrankenPhpFunctions::$handleRequestBehaviors = [
            [
                'server' => [
                    'REQUEST_METHOD' => 'GET',
                    'HTTP_REQUEST_HEADER' => 'fresh',
                ],
                'result' => false,
            ],
        ];

        $kernel
            ->expects($this->once())
            ->method('handle')
            ->willReturnCallback(
                function (Request $request) use ($response): Response {
                    self::assertSame('bar', $request->server->get('FOO'));
                    self::assertSame('web=1&worker=1', $request->server->get('APP_RUNTIME_MODE'));
                    self::assertSame('fresh', $request->headers->get('request-header'));
                    self::assertFalse($request->headers->has('stale-header'));

                    return $response;
                },
            );
        $kernel->expects($this->once())->method('terminate')->with($this->isInstanceOf(Request::class), $response);

        $runner = new FrankenPhpRunner($kernel, 500);

        self::assertSame(0, $runner->run());
        self::assertSame([true], MockFrankenPhpFunctions::$ignoreUserAbortArguments);
        self::assertSame(1, MockFrankenPhpFunctions::$handleRequestCalls);
        self::assertSame(1, MockFrankenPhpFunctions::$gcCollectCyclesCalls);
        self::assertSame(1, MockFrankenPhpFunctions::$gcMemCachesCalls);
        self::assertSame(0, MockFrankenPhpFunctions::$requestParseBodyCalls);
    }

    public function testRunLoopsUntilHandleRequestReturnsFalseWhenLoopLimitIsUnlimited(): void
    {
        $kernel = $this->createMock(TestKernelInterface::class);
        $response = $this->createResponseMock(3);

        MockFrankenPhpFunctions::$handleRequestBehaviors = [
            ['server' => ['REQUEST_METHOD' => 'GET'], 'result' => true],
            ['server' => ['REQUEST_METHOD' => 'GET'], 'result' => true],
            ['server' => ['REQUEST_METHOD' => 'GET'], 'result' => false],
        ];

        $kernel->expects($this->exactly(3))->method('handle')->willReturn($response);
        $kernel->expects($this->exactly(3))->method('terminate');

        $runner = new FrankenPhpRunner($kernel, -1);

        self::assertSame(0, $runner->run());
        self::assertSame(3, MockFrankenPhpFunctions::$handleRequestCalls);
        self::assertSame(3, MockFrankenPhpFunctions::$gcCollectCyclesCalls);
        self::assertSame(3, MockFrankenPhpFunctions::$gcMemCachesCalls);
    }

    public function testRunStopsWhenLoopLimitIsReached(): void
    {
        $kernel = $this->createMock(TestKernelInterface::class);
        $response = $this->createResponseMock(2);

        MockFrankenPhpFunctions::$handleRequestBehaviors = [
            ['server' => ['REQUEST_METHOD' => 'GET'], 'result' => true],
            ['server' => ['REQUEST_METHOD' => 'GET'], 'result' => true],
            ['server' => ['REQUEST_METHOD' => 'GET'], 'result' => true],
        ];

        $kernel->expects($this->exactly(2))->method('handle')->willReturn($response);
        $kernel->expects($this->exactly(2))->method('terminate');

        $runner = new FrankenPhpRunner($kernel, 1);

        self::assertSame(0, $runner->run());
        self::assertSame(2, MockFrankenPhpFunctions::$handleRequestCalls);
        self::assertSame(2, MockFrankenPhpFunctions::$gcCollectCyclesCalls);
        self::assertSame(2, MockFrankenPhpFunctions::$gcMemCachesCalls);
    }

    public function testRunDoesNotAttemptTerminationForNonTerminableKernel(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $response = $this->createResponseMock(1);

        MockFrankenPhpFunctions::$handleRequestBehaviors = [
            ['server' => ['REQUEST_METHOD' => 'GET'], 'result' => false],
        ];

        $kernel->expects($this->once())->method('handle')->willReturn($response);

        $runner = new FrankenPhpRunner($kernel, 500);

        self::assertSame(0, $runner->run());
        self::assertSame(1, MockFrankenPhpFunctions::$handleRequestCalls);
        self::assertSame(1, MockFrankenPhpFunctions::$gcCollectCyclesCalls);
        self::assertSame(1, MockFrankenPhpFunctions::$gcMemCachesCalls);
    }

    public function testRunBuildsPutRequestsFromParsedBody(): void
    {
        $kernel = $this->createMock(TestKernelInterface::class);
        $response = $this->createResponseMock(1);

        MockFrankenPhpFunctions::$handleRequestBehaviors = [
            ['server' => ['REQUEST_METHOD' => 'PUT'], 'result' => false],
        ];
        MockFrankenPhpFunctions::$requestParseBodyResults = [
            [['email' => 'worker@example.com'], ['avatar' => ['name' => 'avatar.png']]],
        ];

        $kernel
            ->expects($this->once())
            ->method('handle')
            ->willReturnCallback(
                function (Request $request) use ($response): Response {
                    self::assertSame('PUT', $request->getMethod());
                    self::assertSame('worker@example.com', $request->request->get('email'));
                    self::assertTrue($request->files->has('avatar'));

                    return $response;
                },
            );
        $kernel->expects($this->once())->method('terminate');

        $runner = new FrankenPhpRunner($kernel, 500);

        self::assertSame(0, $runner->run());
        self::assertSame(1, MockFrankenPhpFunctions::$requestParseBodyCalls);
    }

    public function testRunFallsBackToPhpSuperglobalsWhenRequestBodyParsingFails(): void
    {
        $kernel = $this->createMock(TestKernelInterface::class);
        $response = $this->createResponseMock(1);

        $_POST = ['fallback' => 'value'];
        $_FILES = ['document' => ['name' => 'contract.pdf']];
        MockFrankenPhpFunctions::$requestParseBodyException = new \RequestParseBodyException('failed');
        MockFrankenPhpFunctions::$handleRequestBehaviors = [
            ['server' => ['REQUEST_METHOD' => 'PATCH'], 'result' => false],
        ];

        $kernel
            ->expects($this->once())
            ->method('handle')
            ->willReturnCallback(
                function (Request $request) use ($response): Response {
                    self::assertSame('PATCH', $request->getMethod());
                    self::assertSame('value', $request->request->get('fallback'));
                    self::assertTrue($request->files->has('document'));

                    return $response;
                },
            );
        $kernel->expects($this->once())->method('terminate');

        $runner = new FrankenPhpRunner($kernel, 500);

        self::assertSame(0, $runner->run());
        self::assertSame(1, MockFrankenPhpFunctions::$requestParseBodyCalls);
    }

    public function testRunBuildsLegacyFormEncodedRequestsWhenRequestBodyParserIsUnavailable(): void
    {
        $kernel = $this->createMock(TestKernelInterface::class);
        $response = $this->createResponseMock(1);

        MockFrankenPhpFunctions::$fileGetContentsResult = 'legacy=value';
        MockFrankenPhpFunctions::$handleRequestBehaviors = [
            [
                'server' => [
                    'REQUEST_METHOD' => 'PATCH',
                    'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                ],
                'result' => false,
            ],
        ];

        $kernel
            ->expects($this->once())
            ->method('handle')
            ->willReturnCallback(
                function (Request $request) use ($response): Response {
                    self::assertSame('PATCH', $request->getMethod());
                    self::assertSame('value', $request->request->get('legacy'));

                    return $response;
                },
            );
        $kernel->expects($this->once())->method('terminate');

        $runner = new FrankenPhpRunner($kernel, 500, static fn (): bool => false);

        self::assertSame(0, $runner->run());
        self::assertSame(1, MockFrankenPhpFunctions::$fileGetContentsCalls);
        self::assertSame(0, MockFrankenPhpFunctions::$requestParseBodyCalls);
    }

    public function testRunKeepsPostPayloadForLegacyNonFormRequests(): void
    {
        $kernel = $this->createMock(TestKernelInterface::class);
        $response = $this->createResponseMock(1);

        $_POST = ['fallback' => 'value'];
        MockFrankenPhpFunctions::$fileGetContentsResult = 'legacy=ignored';
        MockFrankenPhpFunctions::$handleRequestBehaviors = [
            [
                'server' => [
                    'REQUEST_METHOD' => 'PATCH',
                    'CONTENT_TYPE' => 'application/json',
                ],
                'result' => false,
            ],
        ];

        $kernel
            ->expects($this->once())
            ->method('handle')
            ->willReturnCallback(
                function (Request $request) use ($response): Response {
                    self::assertSame('PATCH', $request->getMethod());
                    self::assertSame('value', $request->request->get('fallback'));

                    return $response;
                },
            );
        $kernel->expects($this->once())->method('terminate');

        $runner = new FrankenPhpRunner($kernel, 500, static fn (): bool => false);

        self::assertSame(0, $runner->run());
        self::assertSame(0, MockFrankenPhpFunctions::$fileGetContentsCalls);
        self::assertSame(0, MockFrankenPhpFunctions::$requestParseBodyCalls);
    }

    private function createResponseMock(int $expectedSendCalls): Response
    {
        $response = $this->getMockBuilder(Response::class)
            ->onlyMethods(['send'])
            ->getMock();
        $response
            ->expects($this->exactly($expectedSendCalls))
            ->method('send')
            ->willReturnSelf();

        return $response;
    }
}
