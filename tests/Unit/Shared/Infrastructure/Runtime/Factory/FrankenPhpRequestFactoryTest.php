<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Runtime\Factory;

use App\Shared\Infrastructure\Runtime\Factory\FrankenPhpRequestFactory;
use App\Shared\Infrastructure\Runtime\Factory\FrankenPhpRequestGlobalsReader;
use App\Shared\Infrastructure\Runtime\Factory\FrankenPhpRequestGlobalsReaderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class FrankenPhpRequestFactoryTest extends TestCase
{
    public function testCreateBaseRequestDelegatesToInjectedGlobalsReader(): void
    {
        $request = Request::create('/runtime-factory', 'GET');
        $factory = new FrankenPhpRequestFactory(
            requestGlobalsReader: new class ($request) implements FrankenPhpRequestGlobalsReaderInterface {
                public function __construct(private readonly Request $request)
                {
                }

                #[\Override]
                public function readRequest(): Request
                {
                    return $this->request;
                }
            },
        );

        self::assertSame($request, $factory->createBaseRequest());
    }

    public function testDefaultGlobalsReaderReturnsRequestInstance(): void
    {
        $request = (new FrankenPhpRequestGlobalsReader())->readRequest();

        self::assertInstanceOf(Request::class, $request);
    }
}
