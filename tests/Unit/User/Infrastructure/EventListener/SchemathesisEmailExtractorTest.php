<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\EventListener;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\EventListener\SchemathesisBatchUsersEmailExtractor;
use App\User\Infrastructure\EventListener\SchemathesisCleanupEvaluator;
use App\User\Infrastructure\EventListener\SchemathesisEmailExtractor;
use App\User\Infrastructure\EventListener\SchemathesisPayloadDecoder;
use App\User\Infrastructure\EventListener\SchemathesisSingleUserEmailExtractor;
use Symfony\Component\HttpFoundation\Request;

final class SchemathesisEmailExtractorTest extends UnitTestCase
{
    public function testExtractReturnsEmptyListWhenPayloadEmpty(): void
    {
        $request = Request::create('/api/users');

        $evaluator = $this->createMock(SchemathesisCleanupEvaluator::class);
        $evaluator->method('isSingleUserPath')->willReturn(true);

        $decoder = $this->createMock(SchemathesisPayloadDecoder::class);
        $decoder->method('decode')->willReturn([]);

        $single = $this->createMock(SchemathesisSingleUserEmailExtractor::class);
        $single->expects($this->never())->method('extract');

        $batch = $this->createMock(SchemathesisBatchUsersEmailExtractor::class);
        $batch->expects($this->never())->method('extract');

        $extractor = new SchemathesisEmailExtractor($evaluator, $decoder, $single, $batch);

        $this->assertSame([], $extractor->extract($request));
    }

    public function testExtractDelegatesToSingleUserExtractor(): void
    {
        $request = Request::create('/api/users');

        $evaluator = $this->createMock(SchemathesisCleanupEvaluator::class);
        $evaluator->method('isSingleUserPath')->with($request)->willReturn(true);

        $payload = ['email' => 'single@example.com'];

        $decoder = $this->createMock(SchemathesisPayloadDecoder::class);
        $decoder->method('decode')->with($request)->willReturn($payload);

        $single = $this->createMock(SchemathesisSingleUserEmailExtractor::class);
        $single->expects($this->once())
            ->method('extract')
            ->with($payload)
            ->willReturn(['single@example.com']);

        $batch = $this->createMock(SchemathesisBatchUsersEmailExtractor::class);
        $batch->expects($this->never())->method('extract');

        $extractor = new SchemathesisEmailExtractor($evaluator, $decoder, $single, $batch);

        $this->assertSame(['single@example.com'], $extractor->extract($request));
    }

    public function testExtractDelegatesToBatchExtractor(): void
    {
        $request = Request::create('/api/users/batch');

        $evaluator = $this->createMock(SchemathesisCleanupEvaluator::class);
        $evaluator->method('isSingleUserPath')->with($request)->willReturn(false);

        $payload = ['users' => [['email' => 'batch@example.com']]];

        $decoder = $this->createMock(SchemathesisPayloadDecoder::class);
        $decoder->method('decode')->with($request)->willReturn($payload);

        $single = $this->createMock(SchemathesisSingleUserEmailExtractor::class);
        $single->expects($this->never())->method('extract');

        $batch = $this->createMock(SchemathesisBatchUsersEmailExtractor::class);
        $batch->expects($this->once())
            ->method('extract')
            ->with($payload)
            ->willReturn(['batch@example.com']);

        $extractor = new SchemathesisEmailExtractor($evaluator, $decoder, $single, $batch);

        $this->assertSame(['batch@example.com'], $extractor->extract($request));
    }
}
