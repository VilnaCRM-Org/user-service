<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Runtime;

use App\Shared\Infrastructure\Runtime\FrankenPhpRunner;
use App\Shared\Infrastructure\Runtime\MockFrankenPhpFunctions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class FrankenPhpRunnerTest extends TestCase
{
    /**
     * @var array{
     *     server: array<string, array<string, scalar|null>|scalar|null>,
     *     post: array<string, array<string, scalar|null>|scalar|null>,
     *     files: array<string, array<string, scalar|null>|scalar|null>
     * }
     */
    private array $originalRequestGlobals = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->originalRequestGlobals = MockFrankenPhpFunctions::snapshotRequestGlobals();
        MockFrankenPhpFunctions::reset();
        MockFrankenPhpFunctions::replaceServer([
            'FOO' => 'bar',
            'HTTP_STALE_HEADER' => 'stale',
        ]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        MockFrankenPhpFunctions::restoreRequestGlobals($this->originalRequestGlobals);
        MockFrankenPhpFunctions::reset();

        parent::tearDown();
    }

    public function testRunPreservesRequestHeadersAndFiltersBootstrapHttpHeaders(): void
    {
        $kernel = $this->createMock(TestKernelInterface::class);
        $this->expectSingleHandledRequest(
            $kernel,
            static function (Request $request): void {
                self::assertPreservedRequestHeaders($request);
            },
        );
        $this->setSingleHandleRequestBehavior([
            'REQUEST_METHOD' => 'GET',
            'HTTP_REQUEST_HEADER' => 'fresh',
        ]);

        self::assertSame(0, $this->runRunner($kernel));
        $this->assertSingleRunMetrics();
    }

    public function testRunLoopsUntilHandleRequestReturnsFalseWhenLoopLimitIsUnlimited(): void
    {
        $kernel = $this->createMock(TestKernelInterface::class);
        $response = $this->createResponseMock(3);

        MockFrankenPhpFunctions::setHandleRequestBehaviors([
            ['server' => ['REQUEST_METHOD' => 'GET'], 'result' => true],
            ['server' => ['REQUEST_METHOD' => 'GET'], 'result' => true],
            ['server' => ['REQUEST_METHOD' => 'GET'], 'result' => false],
        ]);

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
        $response = $this->createResponseMock(1);

        MockFrankenPhpFunctions::setHandleRequestBehaviors([
            ['server' => ['REQUEST_METHOD' => 'GET'], 'result' => true],
            ['server' => ['REQUEST_METHOD' => 'GET'], 'result' => true],
            ['server' => ['REQUEST_METHOD' => 'GET'], 'result' => true],
        ]);

        $kernel->expects($this->once())->method('handle')->willReturn($response);
        $kernel->expects($this->once())->method('terminate');

        $runner = new FrankenPhpRunner($kernel, 1);

        self::assertSame(0, $runner->run());
        self::assertSame(1, MockFrankenPhpFunctions::$handleRequestCalls);
        self::assertSame(1, MockFrankenPhpFunctions::$gcCollectCyclesCalls);
        self::assertSame(1, MockFrankenPhpFunctions::$gcMemCachesCalls);
    }

    public function testRunDoesNotAttemptTerminationForNonTerminableKernel(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $response = $this->createResponseMock(1);

        MockFrankenPhpFunctions::setHandleRequestBehaviors([
            ['server' => ['REQUEST_METHOD' => 'GET'], 'result' => false],
        ]);

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
        $this->expectSingleHandledRequest(
            $kernel,
            static function (Request $request): void {
                self::assertSame('PUT', $request->getMethod());
                self::assertSame('worker@example.com', $request->request->get('email'));
                self::assertTrue($request->files->has('avatar'));
            },
        );

        MockFrankenPhpFunctions::setHandleRequestBehaviors([
            ['server' => ['REQUEST_METHOD' => 'PUT'], 'result' => false],
        ]);
        MockFrankenPhpFunctions::setRequestParseBodyResults([
            [['email' => 'worker@example.com'], ['avatar' => ['name' => 'avatar.png']]],
        ]);

        self::assertSame(0, $this->runRunner($kernel));
        self::assertSame(1, MockFrankenPhpFunctions::$requestParseBodyCalls);
    }

    public function testRunFallsBackToPhpSuperglobalsWhenRequestBodyParsingFails(): void
    {
        $kernel = $this->createMock(TestKernelInterface::class);
        $this->expectSingleHandledRequest(
            $kernel,
            static function (Request $request): void {
                self::assertSame('PATCH', $request->getMethod());
                self::assertSame('value', $request->request->get('fallback'));
                self::assertTrue($request->files->has('document'));
            },
        );

        MockFrankenPhpFunctions::replacePost(['fallback' => 'value']);
        MockFrankenPhpFunctions::replaceFiles(['document' => ['name' => 'contract.pdf']]);
        MockFrankenPhpFunctions::setRequestParseBodyException(
            new \RequestParseBodyException('failed'),
        );
        MockFrankenPhpFunctions::setHandleRequestBehaviors([
            ['server' => ['REQUEST_METHOD' => 'PATCH'], 'result' => false],
        ]);

        self::assertSame(0, $this->runRunner($kernel));
        self::assertSame(1, MockFrankenPhpFunctions::$requestParseBodyCalls);
    }

    public function testRunBuildsLegacyFormEncodedRequestsWhenRequestBodyParserIsUnavailable(): void
    {
        $kernel = $this->createMock(TestKernelInterface::class);
        $this->expectSingleHandledRequest(
            $kernel,
            static function (Request $request): void {
                self::assertRequestPayload($request, 'legacy', 'value');
            },
        );

        MockFrankenPhpFunctions::setFileGetContentsResult('legacy=value');
        $this->setSinglePatchHandleRequestBehavior('application/x-www-form-urlencoded');

        self::assertSame(0, $this->runRunner($kernel, static fn (): bool => false));
        $this->assertSingleRunMetrics(fileGetContentsCalls: 1);
    }

    public function testRunPreservesExistingPostPayloadWhenLegacyInputStreamIsEmpty(): void
    {
        $kernel = $this->createMock(TestKernelInterface::class);
        $this->expectSingleHandledRequest(
            $kernel,
            static function (Request $request): void {
                self::assertRequestPayload($request, 'fallback', 'value');
            },
        );

        MockFrankenPhpFunctions::replacePost(['fallback' => 'value']);
        MockFrankenPhpFunctions::setFileGetContentsResult('');
        $this->setSinglePatchHandleRequestBehavior('application/x-www-form-urlencoded');

        self::assertSame(0, $this->runRunner($kernel, static fn (): bool => false));
        $this->assertSingleRunMetrics(fileGetContentsCalls: 1);
    }

    public function testRunKeepsPostPayloadForLegacyNonFormRequests(): void
    {
        $kernel = $this->createMock(TestKernelInterface::class);
        $this->expectSingleHandledRequest(
            $kernel,
            static function (Request $request): void {
                self::assertRequestPayload($request, 'fallback', 'value');
            },
        );

        MockFrankenPhpFunctions::replacePost(['fallback' => 'value']);
        MockFrankenPhpFunctions::setFileGetContentsResult('legacy=ignored');
        $this->setSinglePatchHandleRequestBehavior('application/json');

        self::assertSame(0, $this->runRunner($kernel, static fn (): bool => false));
        $this->assertSingleRunMetrics();
    }

    private function expectSingleHandledRequest(
        TestKernelInterface $kernel,
        callable $assertRequest,
    ): void {
        $response = $this->createResponseMock(1);

        $kernel
            ->expects($this->once())
            ->method('handle')
            ->willReturnCallback(
                static function (Request $request) use ($assertRequest, $response): Response {
                    $assertRequest($request);

                    return $response;
                },
            );
        $kernel->expects($this->once())->method('terminate');
    }

    private function runRunner(
        HttpKernelInterface $kernel,
        ?\Closure $bodyParserChecker = null,
    ): int {
        $runner = new FrankenPhpRunner($kernel, 500, $bodyParserChecker);

        return $runner->run();
    }

    private function assertSingleRunMetrics(
        int $requestParseBodyCalls = 0,
        int $fileGetContentsCalls = 0,
    ): void {
        self::assertSame([true], MockFrankenPhpFunctions::$ignoreUserAbortArguments);
        self::assertSame(1, MockFrankenPhpFunctions::$handleRequestCalls);
        self::assertSame(1, MockFrankenPhpFunctions::$gcCollectCyclesCalls);
        self::assertSame(1, MockFrankenPhpFunctions::$gcMemCachesCalls);
        self::assertSame($requestParseBodyCalls, MockFrankenPhpFunctions::$requestParseBodyCalls);
        self::assertSame($fileGetContentsCalls, MockFrankenPhpFunctions::$fileGetContentsCalls);
    }

    /**
     * @param array<string, array<string, scalar|null>|scalar|null> $server
     */
    private function setSingleHandleRequestBehavior(array $server): void
    {
        MockFrankenPhpFunctions::setHandleRequestBehaviors([
            ['server' => $server, 'result' => false],
        ]);
    }

    private function setSinglePatchHandleRequestBehavior(string $contentType): void
    {
        $this->setSingleHandleRequestBehavior([
            'REQUEST_METHOD' => 'PATCH',
            'CONTENT_TYPE' => $contentType,
        ]);
    }

    private static function assertPreservedRequestHeaders(Request $request): void
    {
        self::assertSame('bar', $request->server->get('FOO'));
        self::assertSame('web=1&worker=1', $request->server->get('APP_RUNTIME_MODE'));
        self::assertSame('fresh', $request->headers->get('request-header'));
        self::assertFalse($request->headers->has('stale-header'));
    }

    private static function assertRequestPayload(
        Request $request,
        string $field,
        string $value,
    ): void {
        self::assertSame('PATCH', $request->getMethod());
        self::assertSame($value, $request->request->get($field));
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
