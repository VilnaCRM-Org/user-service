<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\QueryParameter\Pagination;

use App\Shared\Application\QueryParameter\Evaluator\ExplicitValueEvaluator;
use App\Shared\Application\QueryParameter\Normalizer\PositiveIntegerNormalizer;
use App\Shared\Application\QueryParameter\Pagination\ItemsPerPageRule;
use App\Shared\Application\QueryParameter\QueryParameterViolation;
use App\Shared\Application\QueryParameter\QueryParameterViolationFactory;
use App\Tests\Unit\UnitTestCase;

final class ItemsPerPageRuleTest extends UnitTestCase
{
    public function testDoesNotNormalizeWhenValueIsNotExplicit(): void
    {
        $valueEvaluator = $this->createMock(ExplicitValueEvaluator::class);
        $normalizer = $this->createMock(PositiveIntegerNormalizer::class);
        $violationFactory = $this->createMock(QueryParameterViolationFactory::class);
        $violation = new QueryParameterViolation('Invalid pagination', 'detail');

        $valueEvaluator->expects(self::once())
            ->method('wasParameterSent')
            ->with('   ')
            ->willReturn(true);

        $valueEvaluator->expects(self::once())
            ->method('isExplicitlyProvided')
            ->with('   ')
            ->willReturn(false);

        $normalizer->expects(self::never())->method('normalize');

        $violationFactory->expects(self::once())
            ->method('invalidPagination')
            ->willReturn($violation);

        $rule = new ItemsPerPageRule(
            $valueEvaluator,
            $normalizer,
            $violationFactory
        );

        self::assertSame($violation, $rule->evaluate('   '));
    }
}
