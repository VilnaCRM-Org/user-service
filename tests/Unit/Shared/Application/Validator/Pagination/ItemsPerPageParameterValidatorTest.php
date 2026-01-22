<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator\Pagination;

use App\Shared\Application\QueryParameter\Pagination\ItemsPerPageRule;
use App\Shared\Application\QueryParameter\QueryParameterViolation;
use App\Shared\Application\Validator\Pagination\ItemsPerPageParameterValidator;
use App\Tests\Unit\UnitTestCase;

final class ItemsPerPageParameterValidatorTest extends UnitTestCase
{
    public function testReturnsNullWhenItemsPerPageNotPresent(): void
    {
        $rule = $this->createMock(ItemsPerPageRule::class);
        $rule->expects(self::never())->method('evaluate');

        $validator = new ItemsPerPageParameterValidator($rule);

        $result = $validator->validate([]);

        self::assertNull($result);
    }

    public function testReturnsNullWhenItemsPerPageNotPresentEvenWithOtherParams(): void
    {
        $rule = $this->createMock(ItemsPerPageRule::class);
        $rule->expects(self::never())->method('evaluate');

        $validator = new ItemsPerPageParameterValidator($rule);

        $result = $validator->validate(['page' => 1, 'order' => 'asc']);

        self::assertNull($result);
    }

    public function testDelegatesValidationToRuleWhenItemsPerPagePresent(): void
    {
        $rule = $this->createMock(ItemsPerPageRule::class);
        $rule->expects(self::once())
            ->method('evaluate')
            ->with(10)
            ->willReturn(null);

        $validator = new ItemsPerPageParameterValidator($rule);

        $result = $validator->validate(['itemsPerPage' => 10]);

        self::assertNull($result);
    }

    public function testReturnsViolationFromRule(): void
    {
        $violation = $this->createMock(QueryParameterViolation::class);

        $rule = $this->createMock(ItemsPerPageRule::class);
        $rule->expects(self::once())
            ->method('evaluate')
            ->with(0)
            ->willReturn($violation);

        $validator = new ItemsPerPageParameterValidator($rule);

        $result = $validator->validate(['itemsPerPage' => 0]);

        self::assertSame($violation, $result);
    }

    public function testExplicitlyReturnsNullNotVoid(): void
    {
        $rule = $this->createMock(ItemsPerPageRule::class);
        $rule->expects(self::never())->method('evaluate');

        $validator = new ItemsPerPageParameterValidator($rule);

        // This test ensures the method returns null (not void/no return)
        // If the return statement is removed, this would fail
        $result = $validator->validate(['someOtherKey' => 'value']);

        self::assertNull($result);
        self::assertTrue($result === null); // Extra assertion to catch void return
    }
}
