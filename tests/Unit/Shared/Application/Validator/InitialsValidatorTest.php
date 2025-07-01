<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\Initials;
use App\Shared\Application\Validator\InitialsValidator;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InitialsValidatorTest extends UnitTestCase
{
    private InitialsValidator $validator;
    private ExecutionContextInterface $context;
    private Constraint $constraint;
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->validator = new InitialsValidator($this->translator);
        $this->validator->initialize($this->context);
        $this->constraint = $this->createMock(Initials::class);
    }

    public function testValidValue(): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');
        $this->validator->validate(
            $this->faker->firstName() . ' ' . $this->faker->lastName(),
            $this->constraint
        );
    }

    public function testOptional(): void
    {
        $this->constraint->expects($this->once())
            ->method('isOptional')
            ->willReturn(true);
        $this->context->expects($this->never())
            ->method('buildViolation');
        $this->validator->validate(
            '',
            $this->constraint
        );
    }

    public function testOptionalDefaultValue(): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');
        $this->validator->validate(
            '',
            new Initials()
        );
    }

    public function testPassedSpaces(): void
    {
        $constraintViolationBuilder = $this->createMock(
            ConstraintViolationBuilderInterface::class
        );
        $error = $this->faker->word();
        $this->translator->method('trans')
            ->with('initials.spaces')
            ->willReturn($error);
        $this->context->method('buildViolation')
            ->with($error)
            ->willReturn($constraintViolationBuilder);
        $constraintViolationBuilder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate(
            ' ',
            $this->constraint
        );
    }
}
