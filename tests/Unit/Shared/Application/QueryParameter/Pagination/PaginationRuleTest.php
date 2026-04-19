<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\QueryParameter\Pagination;

use App\Shared\Application\QueryParameter\Pagination\PaginationRule;
use App\Shared\Application\QueryParameter\QueryParameterViolation;
use App\Shared\Application\Validator\Pagination\ItemsPerPageParameterValidator;
use App\Shared\Application\Validator\Pagination\PageParameterValidator;
use App\Shared\Application\Validator\Pagination\PartialParameterValidator;
use App\Tests\Unit\UnitTestCase;

final class PaginationRuleTest extends UnitTestCase
{
    public function testReturnsPageViolationWithoutCallingItemsPerPageValidator(): void
    {
        $query = ['page' => 'invalid'];
        $violation = $this->createPaginationViolation();
        $rule = $this->createRule(
            $this->createPageValidator($query, $violation),
            $this->createNeverValidatingItemsPerPageValidator(),
            $this->createNeverValidatingPartialParameterValidator()
        );

        self::assertSame($violation, $rule->validate('/api/users', $query));
    }

    public function testSkipsValidatorsForDifferentPaths(): void
    {
        $rule = $this->createRule(
            $this->createNeverValidatingPageValidator(),
            $this->createNeverValidatingItemsPerPageValidator(),
            $this->createNeverValidatingPartialParameterValidator()
        );

        self::assertNull($rule->validate('/api/health', ['page' => 1]));
    }

    public function testDelegatesToPartialValidatorAfterPaginationChecks(): void
    {
        $query = ['partial' => 'garbage'];
        $violation = $this->createPartialPaginationViolation();
        $rule = $this->createRule(
            $this->createPageValidator($query),
            $this->createItemsPerPageValidator($query),
            $this->createPartialParameterValidator($query, $violation)
        );

        self::assertSame($violation, $rule->validate('/api/users', $query));
    }

    private function createRule(
        PageParameterValidator $pageValidator,
        ItemsPerPageParameterValidator $itemsPerPageValidator,
        PartialParameterValidator $partialParameterValidator
    ): PaginationRule {
        return new PaginationRule(
            $pageValidator,
            $itemsPerPageValidator,
            $partialParameterValidator
        );
    }

    private function createPaginationViolation(): QueryParameterViolation
    {
        return new QueryParameterViolation(
            'Invalid pagination value',
            'Page and itemsPerPage must be greater than or equal to 1.'
        );
    }

    private function createPartialPaginationViolation(): QueryParameterViolation
    {
        return new QueryParameterViolation(
            'Invalid partial pagination value',
            'The partial parameter must be either true, false, 1, or 0.'
        );
    }

    /**
     * @param array<string, array|string|int|float|bool|null> $query
     */
    private function createPageValidator(
        array $query,
        ?QueryParameterViolation $violation = null
    ): PageParameterValidator {
        $validator = $this->createMock(PageParameterValidator::class);
        $validator->expects(self::once())
            ->method('validate')
            ->with($query)
            ->willReturn($violation);

        return $validator;
    }

    /**
     * @param array<string, array|string|int|float|bool|null> $query
     */
    private function createItemsPerPageValidator(array $query): ItemsPerPageParameterValidator
    {
        $validator = $this->createMock(ItemsPerPageParameterValidator::class);
        $validator->expects(self::once())
            ->method('validate')
            ->with($query)
            ->willReturn(null);

        return $validator;
    }

    /**
     * @param array<string, array|string|int|float|bool|null> $query
     */
    private function createPartialParameterValidator(
        array $query,
        ?QueryParameterViolation $violation = null
    ): PartialParameterValidator {
        $validator = $this->createMock(PartialParameterValidator::class);
        $validator->expects(self::once())
            ->method('validate')
            ->with($query)
            ->willReturn($violation);

        return $validator;
    }

    private function createNeverValidatingPageValidator(): PageParameterValidator
    {
        $validator = $this->createMock(PageParameterValidator::class);
        $validator->expects(self::never())->method('validate');

        return $validator;
    }

    private function createNeverValidatingItemsPerPageValidator(): ItemsPerPageParameterValidator
    {
        $validator = $this->createMock(ItemsPerPageParameterValidator::class);
        $validator->expects(self::never())->method('validate');

        return $validator;
    }

    private function createNeverValidatingPartialParameterValidator(): PartialParameterValidator
    {
        $validator = $this->createMock(PartialParameterValidator::class);
        $validator->expects(self::never())->method('validate');

        return $validator;
    }
}
