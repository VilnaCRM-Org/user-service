<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator\Pagination;

use App\Shared\Application\Factory\QueryParameterViolationFactory;
use App\Shared\Application\QueryParameter\Evaluator\ExplicitValueEvaluator;
use App\Shared\Application\QueryParameter\Normalizer\PositiveIntegerNormalizer;
use App\Shared\Application\QueryParameter\QueryParameterViolation;
use App\Shared\Application\Validator\Pagination\PageParameterValidator;
use App\Tests\Unit\UnitTestCase;

final class PageParameterValidatorTest extends UnitTestCase
{
    public function testReturnsNullWhenPageNotPresent(): void
    {
        $valueEvaluator = $this->createMock(ExplicitValueEvaluator::class);
        $valueEvaluator->expects(self::never())->method('isExplicitlyProvided');

        $normalizer = $this->createMock(PositiveIntegerNormalizer::class);
        $normalizer->expects(self::never())->method('normalize');

        $violationFactory = $this->createMock(QueryParameterViolationFactory::class);

        $validator = new PageParameterValidator($valueEvaluator, $normalizer, $violationFactory);

        $result = $this->withoutPhpWarnings(static fn () => $validator->validate([]));

        self::assertNull($result);
    }

    public function testReturnsNullWhenPageNotPresentEvenWithOtherParams(): void
    {
        $valueEvaluator = $this->createMock(ExplicitValueEvaluator::class);
        $valueEvaluator->expects(self::never())->method('isExplicitlyProvided');

        $normalizer = $this->createMock(PositiveIntegerNormalizer::class);
        $violationFactory = $this->createMock(QueryParameterViolationFactory::class);

        $validator = new PageParameterValidator($valueEvaluator, $normalizer, $violationFactory);

        $result = $this->withoutPhpWarnings(
            static fn () => $validator->validate(['itemsPerPage' => 10, 'order' => 'asc'])
        );

        self::assertNull($result);
    }

    public function testMissingPageDoesNotEmitWarningsOrInvokeEvaluators(): void
    {
        $valueEvaluator = $this->createMock(ExplicitValueEvaluator::class);
        $valueEvaluator->expects(self::never())->method('isExplicitlyProvided');
        $valueEvaluator->expects(self::never())->method('wasParameterSent');

        $normalizer = $this->createMock(PositiveIntegerNormalizer::class);
        $violationFactory = $this->createMock(QueryParameterViolationFactory::class);

        $validator = new PageParameterValidator($valueEvaluator, $normalizer, $violationFactory);

        $result = $this->withoutPhpWarnings(
            fn () => $validator->validate(['itemsPerPage' => $this->faker->numberBetween(2, 20)])
        );

        self::assertNull($result);
    }

    public function testReturnsNullForValidExplicitPage(): void
    {
        $valueEvaluator = $this->createMock(ExplicitValueEvaluator::class);
        $valueEvaluator->expects(self::once())
            ->method('isExplicitlyProvided')
            ->with(1)
            ->willReturn(true);

        $normalizer = $this->createMock(PositiveIntegerNormalizer::class);
        $normalizer->expects(self::once())
            ->method('normalize')
            ->with(1)
            ->willReturn(1);

        $violationFactory = $this->createMock(QueryParameterViolationFactory::class);

        $validator = new PageParameterValidator($valueEvaluator, $normalizer, $violationFactory);

        $result = $validator->validate(['page' => 1]);

        self::assertNull($result);
    }

    public function testReturnsViolationForInvalidExplicitPage(): void
    {
        $violation = $this->createMock(QueryParameterViolation::class);

        $valueEvaluator = $this->createMock(ExplicitValueEvaluator::class);
        $valueEvaluator->expects(self::once())
            ->method('isExplicitlyProvided')
            ->with('invalid')
            ->willReturn(true);

        $normalizer = $this->createMock(PositiveIntegerNormalizer::class);
        $normalizer->expects(self::once())
            ->method('normalize')
            ->with('invalid')
            ->willReturn(null);

        $violationFactory = $this->createMock(QueryParameterViolationFactory::class);
        $violationFactory->expects(self::once())
            ->method('invalidPagination')
            ->willReturn($violation);

        $validator = new PageParameterValidator($valueEvaluator, $normalizer, $violationFactory);

        $result = $validator->validate(['page' => 'invalid']);

        self::assertSame($violation, $result);
    }

    public function testPagePresentButNotExplicitReturnsViolationIfParameterWasSent(): void
    {
        $violation = $this->createMock(QueryParameterViolation::class);

        $valueEvaluator = $this->createMock(ExplicitValueEvaluator::class);
        $valueEvaluator->expects(self::once())
            ->method('isExplicitlyProvided')
            ->with(['nested' => 'value'])
            ->willReturn(false);

        $valueEvaluator->expects(self::once())
            ->method('wasParameterSent')
            ->with(['nested' => 'value'])
            ->willReturn(true);

        $normalizer = $this->createMock(PositiveIntegerNormalizer::class);
        $violationFactory = $this->createMock(QueryParameterViolationFactory::class);
        $violationFactory->expects(self::once())
            ->method('invalidPagination')
            ->willReturn($violation);

        $validator = new PageParameterValidator($valueEvaluator, $normalizer, $violationFactory);

        $result = $validator->validate(['page' => ['nested' => 'value']]);

        self::assertSame($violation, $result);
    }

    public function testExplicitlyReturnsNullWhenPageKeyMissing(): void
    {
        $valueEvaluator = $this->createMock(ExplicitValueEvaluator::class);
        // Should never be called when page key is missing
        $valueEvaluator->expects(self::never())->method('isExplicitlyProvided');

        $normalizer = $this->createMock(PositiveIntegerNormalizer::class);
        $violationFactory = $this->createMock(QueryParameterViolationFactory::class);

        $validator = new PageParameterValidator($valueEvaluator, $normalizer, $violationFactory);

        $result = $this->withoutPhpWarnings(
            static fn () => $validator->validate(['someOtherKey' => 'value'])
        );

        self::assertNull($result);
        self::assertTrue($result === null);
    }

    public function testReturnsNullWhenPageKeyPresentMatchesFirstArm(): void
    {
        $valueEvaluator = $this->createMock(ExplicitValueEvaluator::class);
        // The first match arm checks if key is NOT present
        // This test ensures when key IS present, we don't match the first arm
        $valueEvaluator->expects(self::once())
            ->method('isExplicitlyProvided')
            ->with(1)
            ->willReturn(true);

        $normalizer = $this->createMock(PositiveIntegerNormalizer::class);
        $normalizer->expects(self::once())
            ->method('normalize')
            ->with(1)
            ->willReturn(1);

        $violationFactory = $this->createMock(QueryParameterViolationFactory::class);

        $validator = new PageParameterValidator($valueEvaluator, $normalizer, $violationFactory);

        // When page=1, should go through explicit value path, not the "missing key" path
        $result = $validator->validate(['page' => 1]);

        self::assertNull($result);
    }
}
