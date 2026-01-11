<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\QueryParameter\Pagination;

use App\Shared\Application\QueryParameter\Pagination\PaginationRule;
use App\Shared\Application\QueryParameter\QueryParameterViolation;
use App\Shared\Application\Validator\Pagination\ItemsPerPageParameterValidator;
use App\Shared\Application\Validator\Pagination\PageParameterValidator;
use App\Tests\Unit\UnitTestCase;

final class PaginationRuleTest extends UnitTestCase
{
    public function testReturnsPageViolationWithoutCallingItemsPerPageValidator(): void
    {
        $query = ['page' => 'invalid'];
        $violation = new QueryParameterViolation(
            'Invalid pagination value',
            'Page and itemsPerPage must be greater than or equal to 1.'
        );

        $pageValidator = $this->createMock(PageParameterValidator::class);
        $pageValidator
            ->expects(self::once())
            ->method('validate')
            ->with($query)
            ->willReturn($violation);

        $itemsPerPageValidator = $this->createMock(ItemsPerPageParameterValidator::class);
        $itemsPerPageValidator
            ->expects(self::never())
            ->method('validate');

        $rule = new PaginationRule($pageValidator, $itemsPerPageValidator);

        self::assertSame(
            $violation,
            $rule->validate('/api/users', $query)
        );
    }

    public function testSkipsValidatorsForDifferentPaths(): void
    {
        $pageValidator = $this->createMock(PageParameterValidator::class);
        $pageValidator->expects(self::never())->method('validate');

        $itemsPerPageValidator = $this->createMock(ItemsPerPageParameterValidator::class);
        $itemsPerPageValidator->expects(self::never())->method('validate');

        $rule = new PaginationRule($pageValidator, $itemsPerPageValidator);

        self::assertNull($rule->validate('/api/health', ['page' => 1]));
    }
}
