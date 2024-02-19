<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\Initials;
use App\Shared\Application\Validator\InitialsValidator;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class InitialsValidatorTest extends UnitTestCase
{
    private InitialsValidator $validator;
    private ExecutionContextInterface $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new InitialsValidator();
        $this->validator->initialize($this->context);
    }

    public function testValidValue(): void
    {
        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validate(
            $this->faker->firstName() . ' ' . $this->faker->lastName(),
            $this->createMock(Initials::class)
        );
    }

    public function testInvalidFormat(): void
    {
        $constraintViolationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('Invalid full name format')
            ->willReturn($constraintViolationBuilder);
        $constraintViolationBuilder->expects($this->once())->method('addViolation');

        $this->validator->validate(
            $this->faker->firstName() . ' ' . $this->faker->numberBetween(1, 100),
            new Initials()
        );
    }

    public function testEmptyParts(): void
    {
        $constraintViolationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->method('buildViolation')
            ->with('Name and surname both should have at least 1 character')
            ->willReturn($constraintViolationBuilder);
        $constraintViolationBuilder->expects($this->once())->method('addViolation');

        $this->validator->validate($this->faker->firstName() . ' ', new Initials());
    }
}
