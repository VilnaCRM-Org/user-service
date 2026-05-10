<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\QueryParameter\Pagination;

use App\Shared\Application\Factory\QueryParameterViolationFactory;
use App\Shared\Application\QueryParameter\Normalizer\PositiveIntegerNormalizer;
use App\Shared\Application\QueryParameter\Pagination\ItemsPerPageRule;
use App\Shared\Application\QueryParameter\QueryParameterViolation;
use App\Shared\Application\QueryParameter\Validator\ExplicitValueValidator;
use App\Tests\Unit\UnitTestCase;

final class ItemsPerPageRuleTest extends UnitTestCase
{
    public function testDoesNotNormalizeWhenValueIsNotExplicit(): void
    {
        $violation = new QueryParameterViolation('Invalid pagination', 'detail');
        $valueValidator = $this->createValueValidatorForNonExplicitValue();
        $normalizer = $this->createNormalizerThatShouldNotBeCalled();
        $violationFactory = $this->createViolationFactory($violation);
        $rule = new ItemsPerPageRule($valueValidator, $normalizer, $violationFactory);
        self::assertSame($violation, $rule->evaluate('   '));
    }

    private function createValueValidatorForNonExplicitValue(): ExplicitValueValidator
    {
        $evaluator = $this->createMock(ExplicitValueValidator::class);
        $evaluator->expects(self::once())
            ->method('wasParameterSent')
            ->with('   ')
            ->willReturn(true);
        $evaluator->expects(self::once())
            ->method('isExplicitlyProvided')
            ->with('   ')
            ->willReturn(false);

        return $evaluator;
    }

    private function createNormalizerThatShouldNotBeCalled(): PositiveIntegerNormalizer
    {
        $normalizer = $this->createMock(PositiveIntegerNormalizer::class);
        $normalizer->expects(self::never())->method('normalize');

        return $normalizer;
    }

    private function createViolationFactory(
        QueryParameterViolation $violation
    ): QueryParameterViolationFactory {
        $factory = $this->createMock(QueryParameterViolationFactory::class);
        $factory->expects(self::once())
            ->method('invalidPagination')
            ->willReturn($violation);

        return $factory;
    }
}
