<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\Password;
use App\Shared\Application\Validator\PasswordValidator;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class PasswordValidatorTest extends UnitTestCase
{
    private PasswordValidator $validator;
    private ExecutionContextInterface $context;
    private Constraint $constraint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new PasswordValidator();
        $this->validator->initialize($this->context);
        $this->constraint = $this->createMock(Password::class);
    }

    public function testValidValue(): void
    {
        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validate(
            'Password123',
            $this->constraint
        );
    }

    public function testOptional(): void
    {
        $this->constraint->expects($this->once())
            ->method('isOptional')
            ->willReturn(true);
        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validate(
            '',
            $this->constraint
        );
    }

    public function testOptionalDefaultValue(): void
    {
        $this->context->expects($this->atLeast(1))->method('buildViolation');
        $this->validator->validate(
            '',
            new Password()
        );
    }

    public function testValidateInvalidPasswordLength(): void
    {
        $constraintViolationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('Password must be between 8 and 64 characters long')
            ->willReturn($constraintViolationBuilder);
        $constraintViolationBuilder->expects($this->once())->method('addViolation');

        $this->validator->validate(
            'Pass1',
            $this->constraint
        );
    }

    public function testValidateInvalidPasswordNoNumber(): void
    {
        $constraintViolationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('Password must contain at least one number')
            ->willReturn($constraintViolationBuilder);
        $constraintViolationBuilder->expects($this->once())->method('addViolation');

        $this->validator->validate(
            'Password',
            $this->constraint
        );
    }

    public function testValidateInvalidPasswordNoUppercase(): void
    {
        $constraintViolationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('Password must contain at least one uppercase letter')
            ->willReturn($constraintViolationBuilder);
        $constraintViolationBuilder->expects($this->once())->method('addViolation');

        $this->validator->validate(
            'password123',
            $this->constraint
        );
    }
}
