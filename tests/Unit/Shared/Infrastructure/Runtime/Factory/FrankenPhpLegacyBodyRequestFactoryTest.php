<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Runtime\Factory;

use App\Shared\Infrastructure\Runtime\Factory\FrankenPhpLegacyBodyRequestFactory;
use App\Shared\Infrastructure\Runtime\MockFrankenPhpFunctions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class FrankenPhpLegacyBodyRequestFactoryTest extends TestCase
{
    private FrankenPhpLegacyBodyRequestFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new FrankenPhpLegacyBodyRequestFactory();
        MockFrankenPhpFunctions::reset();
    }

    #[\Override]
    protected function tearDown(): void
    {
        MockFrankenPhpFunctions::reset();

        parent::tearDown();
    }

    public function testCreatePreservesAttributesForLegacyFormRequests(): void
    {
        MockFrankenPhpFunctions::setFileGetContentsResult('legacy=value');
        $request = new Request(
            ['query' => 'value'],
            ['fallback' => 'value'],
            ['route' => 'legacy'],
            [],
            [],
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
        );

        $rebuiltRequest = $this->factory->create($request);

        self::assertSame(['route' => 'legacy'], $rebuiltRequest->attributes->all());
        self::assertSame('value', $rebuiltRequest->request->get('legacy'));
        self::assertSame('legacy=value', $rebuiltRequest->getContent());
    }

    public function testCreateParsesLegacyFormContentTypeRegardlessOfCaseAndParameters(): void
    {
        MockFrankenPhpFunctions::setFileGetContentsResult('legacy=value');
        $request = new Request(
            [],
            ['fallback' => 'value'],
            [],
            [],
            [],
            ['CONTENT_TYPE' => ' Application/X-Www-Form-Urlencoded; charset=UTF-8 '],
        );

        $rebuiltRequest = $this->factory->create($request);

        self::assertSame('value', $rebuiltRequest->request->get('legacy'));
        self::assertSame(1, MockFrankenPhpFunctions::$interceptedPhpInputCalls);
    }

    public function testCreateTreatsWhitespaceOnlyContentTypeAsEmpty(): void
    {
        MockFrankenPhpFunctions::setFileGetContentsResult('legacy=value');
        $request = new Request(
            [],
            ['fallback' => 'value'],
            [],
            [],
            [],
            ['CONTENT_TYPE' => '   '],
        );

        $rebuiltRequest = $this->factory->create($request);

        self::assertSame('value', $rebuiltRequest->request->get('legacy'));
        self::assertSame(1, MockFrankenPhpFunctions::$interceptedPhpInputCalls);
    }

    public function testCreateTrimsWhitespaceAroundLegacyMediaTypeToken(): void
    {
        MockFrankenPhpFunctions::setFileGetContentsResult('legacy=value');
        $request = new Request(
            [],
            ['fallback' => 'value'],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded ; charset=UTF-8'],
        );

        $rebuiltRequest = $this->factory->create($request);

        self::assertSame('value', $rebuiltRequest->request->get('legacy'));
        self::assertSame(1, MockFrankenPhpFunctions::$interceptedPhpInputCalls);
    }
}
