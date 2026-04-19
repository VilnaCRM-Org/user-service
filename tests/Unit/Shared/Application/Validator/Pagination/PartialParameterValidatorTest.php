<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator\Pagination;

use App\Shared\Application\Factory\QueryParameterViolationFactory;
use App\Shared\Application\QueryParameter\Normalizer\BooleanNormalizer;
use App\Shared\Application\QueryParameter\QueryParameterViolation;
use App\Shared\Application\QueryParameter\Validator\ExplicitValueValidator;
use App\Shared\Application\Validator\Pagination\PartialParameterValidator;
use App\Tests\Unit\UnitTestCase;

final class PartialParameterValidatorTest extends UnitTestCase
{
    public function testReturnsNullWhenPartialIsNotPresent(): void
    {
        $valueValidator = $this->createMock(ExplicitValueValidator::class);
        $valueValidator->expects(self::never())->method('wasParameterSent');

        $normalizer = $this->createMock(BooleanNormalizer::class);
        $normalizer->expects(self::never())->method('normalize');

        $violationFactory = $this->createMock(QueryParameterViolationFactory::class);

        $validator = new PartialParameterValidator($valueValidator, $normalizer, $violationFactory);

        $result = $this->withoutPhpWarnings(static fn () => $validator->validate([]));

        self::assertNull($result);
    }

    public function testReturnsNullWhenPartialWasNotSent(): void
    {
        $valueValidator = $this->createMock(ExplicitValueValidator::class);
        $valueValidator->expects(self::once())
            ->method('wasParameterSent')
            ->with(null)
            ->willReturn(false);
        $valueValidator->expects(self::never())->method('isExplicitlyProvided');

        $normalizer = $this->createMock(BooleanNormalizer::class);
        $normalizer->expects(self::never())->method('normalize');

        $violationFactory = $this->createMock(QueryParameterViolationFactory::class);

        $validator = new PartialParameterValidator($valueValidator, $normalizer, $violationFactory);

        self::assertNull($validator->validate(['partial' => null]));
    }

    public function testReturnsNullForValidPartialParameter(): void
    {
        $valueValidator = $this->createMock(ExplicitValueValidator::class);
        $valueValidator->expects(self::once())
            ->method('wasParameterSent')
            ->with('true')
            ->willReturn(true);
        $valueValidator->expects(self::once())
            ->method('isExplicitlyProvided')
            ->with('true')
            ->willReturn(true);

        $normalizer = $this->createMock(BooleanNormalizer::class);
        $normalizer->expects(self::once())
            ->method('normalize')
            ->with('true')
            ->willReturn(true);

        $violationFactory = $this->createMock(QueryParameterViolationFactory::class);

        $validator = new PartialParameterValidator($valueValidator, $normalizer, $violationFactory);

        self::assertNull($validator->validate(['partial' => 'true']));
    }

    public function testReturnsNullForValidNumericStringPartialParameter(): void
    {
        $valueValidator = $this->createMock(ExplicitValueValidator::class);
        $valueValidator->expects(self::once())
            ->method('wasParameterSent')
            ->with('1')
            ->willReturn(true);
        $valueValidator->expects(self::once())
            ->method('isExplicitlyProvided')
            ->with('1')
            ->willReturn(true);

        $normalizer = $this->createMock(BooleanNormalizer::class);
        $normalizer->expects(self::once())
            ->method('normalize')
            ->with('1')
            ->willReturn(true);

        $violationFactory = $this->createMock(QueryParameterViolationFactory::class);

        $validator = new PartialParameterValidator($valueValidator, $normalizer, $violationFactory);

        self::assertNull($validator->validate(['partial' => '1']));
    }

    public function testReturnsNullForMixedCaseBooleanPartialParameter(): void
    {
        $valueValidator = $this->createMock(ExplicitValueValidator::class);
        $valueValidator->expects(self::once())
            ->method('wasParameterSent')
            ->with('True')
            ->willReturn(true);
        $valueValidator->expects(self::once())
            ->method('isExplicitlyProvided')
            ->with('True')
            ->willReturn(true);

        $normalizer = $this->createMock(BooleanNormalizer::class);
        $normalizer->expects(self::once())
            ->method('normalize')
            ->with('True')
            ->willReturn(true);

        $violationFactory = $this->createMock(QueryParameterViolationFactory::class);

        $validator = new PartialParameterValidator($valueValidator, $normalizer, $violationFactory);

        self::assertNull($validator->validate(['partial' => 'True']));
    }

    public function testReturnsViolationForImplicitPartialValue(): void
    {
        $violation = $this->createMock(QueryParameterViolation::class);

        $validator = new PartialParameterValidator(
            $this->createValueValidator('', true, false),
            $this->createUnusedNormalizer(),
            $this->createViolationFactory($violation)
        );

        self::assertSame($violation, $validator->validate(['partial' => '']));
    }

    public function testReturnsViolationForInvalidPartialValue(): void
    {
        $violation = $this->createMock(QueryParameterViolation::class);
        $validator = new PartialParameterValidator(
            $this->createValueValidator('garbage', true, true),
            $this->createNormalizer('garbage', null),
            $this->createViolationFactory($violation)
        );

        self::assertSame($violation, $validator->validate(['partial' => 'garbage']));
    }

    private function createValueValidator(
        mixed $value,
        bool $wasSent,
        ?bool $isExplicit = null
    ): ExplicitValueValidator {
        $validator = $this->createMock(ExplicitValueValidator::class);
        $validator->expects(self::once())
            ->method('wasParameterSent')
            ->with($value)
            ->willReturn($wasSent);

        if ($isExplicit === null) {
            $validator->expects(self::never())->method('isExplicitlyProvided');

            return $validator;
        }

        $validator->expects(self::once())
            ->method('isExplicitlyProvided')
            ->with($value)
            ->willReturn($isExplicit);

        return $validator;
    }

    private function createNormalizer(mixed $value, ?bool $normalized): BooleanNormalizer
    {
        $normalizer = $this->createMock(BooleanNormalizer::class);
        $normalizer->expects(self::once())
            ->method('normalize')
            ->with($value)
            ->willReturn($normalized);

        return $normalizer;
    }

    private function createUnusedNormalizer(): BooleanNormalizer
    {
        $normalizer = $this->createMock(BooleanNormalizer::class);
        $normalizer->expects(self::never())->method('normalize');

        return $normalizer;
    }

    private function createViolationFactory(
        QueryParameterViolation $violation
    ): QueryParameterViolationFactory {
        $factory = $this->createMock(QueryParameterViolationFactory::class);
        $factory->expects(self::once())
            ->method('invalidPartialPagination')
            ->willReturn($violation);

        return $factory;
    }
}
