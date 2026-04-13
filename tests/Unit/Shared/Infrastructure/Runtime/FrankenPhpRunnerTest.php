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
    protected function setUp(): void
    {
        parent::setUp();

        MockFrankenPhpFunctions::reset();
        $_SERVER['FOO'] = 'bar';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['FOO']);
        MockFrankenPhpFunctions::reset();

        parent::tearDown();
    }

    public function testRunHandlesRequestTerminatesKernelAndCollectsMemory(): void
    {
        $kernel = $this->createMock(TestKernelInterface::class);
        $kernel
            ->expects($this->once())
            ->method('handle')
            ->willReturnCallback(
                function (Request $request, int $type = HttpKernelInterface::MAIN_REQUEST, bool $catch = true): Response {
                    self::assertSame('bar', $request->server->get('FOO'));

                    return new Response();
                },
            );
        $kernel->expects($this->once())->method('terminate');

        $runner = new FrankenPhpRunner($kernel, 500);

        self::assertSame(0, $runner->run());
        self::assertSame([true], MockFrankenPhpFunctions::$ignoreUserAbortArguments);
        self::assertSame(1, MockFrankenPhpFunctions::$handleRequestCalls);
        self::assertSame(1, MockFrankenPhpFunctions::$gcCollectCyclesCalls);
        self::assertSame(1, MockFrankenPhpFunctions::$gcMemCachesCalls);
    }
}
