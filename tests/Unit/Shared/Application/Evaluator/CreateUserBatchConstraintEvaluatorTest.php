<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Evaluator;

use App\Shared\Application\Collector\BatchEmailCollector;
use App\Shared\Application\Evaluator\CreateUserBatchConstraintEvaluator;
use App\Shared\Application\Normalizer\BatchEntriesNormalizer;
use App\Shared\Application\Resolver\BatchEmailResolver;
use App\Tests\Unit\UnitTestCase;

final class CreateUserBatchConstraintEvaluatorTest extends UnitTestCase
{
    private CreateUserBatchConstraintEvaluator $evaluator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $resolver = new BatchEmailResolver();
        $collector = new BatchEmailCollector($resolver);
        $normalizer = new BatchEntriesNormalizer();
        $this->evaluator = new CreateUserBatchConstraintEvaluator(
            $normalizer,
            $collector
        );
    }

    public function testReturnsMessageWhenBatchIsEmpty(): void
    {
        $this->assertSame(['batch.empty'], $this->evaluator->evaluate([]));
    }

    public function testReturnsNoMessagesForValidBatch(): void
    {
        $batch = [
            ['email' => 'user1@example.com'],
            ['email' => 'user2@example.com'],
        ];

        $this->assertSame([], $this->evaluator->evaluate($batch));
    }

    public function testReturnsDuplicateEmailMessage(): void
    {
        $batch = [
            ['email' => 'user@example.com'],
            ['email' => 'USER@example.com'],
        ];

        $this->assertSame(['batch.email.duplicate'], $this->evaluator->evaluate($batch));
    }

    public function testReturnsMissingEmailMessage(): void
    {
        $batch = [
            ['name' => 'Missing email'],
        ];

        $this->assertSame(['batch.email.missing'], $this->evaluator->evaluate($batch));
    }

    public function testReturnsMissingEmailMessageWhenValueIsNotIterable(): void
    {
        $this->assertSame(['batch.email.missing'], $this->evaluator->evaluate('invalid'));
    }

    public function testReturnsMultipleMessagesWhenMissingAndDuplicateDetected(): void
    {
        $batch = [
            ['email' => 'user@example.com'],
            ['email' => 'user@example.com'],
            ['name' => 'Missing'],
        ];

        $this->assertSame(
            ['batch.email.missing', 'batch.email.duplicate'],
            $this->evaluator->evaluate($batch)
        );
    }
}
