<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Schemathesis;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\Converter\SchemathesisPayloadConverter;
use App\User\Infrastructure\Resolver\SchemathesisBatchUsersEmailResolver;
use App\User\Infrastructure\Resolver\SchemathesisCleanupResolver;
use App\User\Infrastructure\Resolver\SchemathesisEmailResolver;
use App\User\Infrastructure\Resolver\SchemathesisSingleUserEmailResolver;
use Symfony\Component\HttpFoundation\Request;

final class SchemathesisEmailResolverTest extends UnitTestCase
{
    public function testExtractReturnsEmptyListWhenPayloadEmpty(): void
    {
        $request = Request::create('/api/users');

        $cleanupResolver = $this->createMock(SchemathesisCleanupResolver::class);
        $cleanupResolver->method('isSingleUserPath')->willReturn(true);

        $payloadConverter = $this->createMock(SchemathesisPayloadConverter::class);
        $payloadConverter->method('decode')->willReturn([]);

        $single = $this->createMock(SchemathesisSingleUserEmailResolver::class);
        $single->expects($this->never())->method('extract');

        $batch = $this->createMock(SchemathesisBatchUsersEmailResolver::class);
        $batch->expects($this->never())->method('extract');

        $emailResolver = new SchemathesisEmailResolver(
            $cleanupResolver,
            $payloadConverter,
            $single,
            $batch
        );

        $this->assertSame([], $emailResolver->extract($request));
    }

    public function testExtractDelegatesToSingleUserExtractor(): void
    {
        $request = Request::create('/api/users');
        $payload = ['email' => 'single@example.com'];

        $cleanupResolver = $this->createMock(SchemathesisCleanupResolver::class);
        $cleanupResolver->method('isSingleUserPath')->with($request)->willReturn(true);

        $payloadConverter = $this->createMock(SchemathesisPayloadConverter::class);
        $payloadConverter->method('decode')->with($request)->willReturn($payload);

        $single = $this->createMock(SchemathesisSingleUserEmailResolver::class);
        $single->expects($this->once())->method('extract')
            ->with($payload)->willReturn(['single@example.com']);

        $batch = $this->createMock(SchemathesisBatchUsersEmailResolver::class);
        $batch->expects($this->never())->method('extract');

        $emailResolver = new SchemathesisEmailResolver(
            $cleanupResolver,
            $payloadConverter,
            $single,
            $batch
        );

        $result = $emailResolver->extract($request);
        $this->assertSame(['single@example.com'], $result);
    }

    public function testExtractDelegatesToBatchExtractor(): void
    {
        $request = Request::create('/api/users/batch');

        $cleanupResolver = $this->createMock(SchemathesisCleanupResolver::class);
        $cleanupResolver->method('isSingleUserPath')->with($request)->willReturn(false);

        $payload = ['users' => [['email' => 'batch@example.com']]];

        $payloadConverter = $this->createMock(SchemathesisPayloadConverter::class);
        $payloadConverter->method('decode')->with($request)->willReturn($payload);

        $single = $this->createMock(SchemathesisSingleUserEmailResolver::class);
        $single->expects($this->never())->method('extract');

        $batch = $this->createMock(SchemathesisBatchUsersEmailResolver::class);
        $batch->expects($this->once())
            ->method('extract')
            ->with($payload)
            ->willReturn(['batch@example.com']);

        $emailResolver = new SchemathesisEmailResolver(
            $cleanupResolver,
            $payloadConverter,
            $single,
            $batch
        );

        $this->assertSame(['batch@example.com'], $emailResolver->extract($request));
    }
}
