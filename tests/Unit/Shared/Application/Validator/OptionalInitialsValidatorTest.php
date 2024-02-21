<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\InitialsValidator;
use App\Shared\Application\Validator\OptionalInitials;
use App\Shared\Application\Validator\OptionalInitialsValidator;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class OptionalInitialsValidatorTest extends UnitTestCase
{
    public function testValidate(): void
    {
        $value = $this->faker->word;
        $constraint = new OptionalInitials();

        $initialsValidatorMock = $this->createMock(InitialsValidator::class);
        $initialsValidatorMock->expects($this->once())
            ->method('validate')
            ->with($value, $constraint);

        $validator = new OptionalInitialsValidator($initialsValidatorMock);
        $validator->initialize($this->createMock(ExecutionContextInterface::class));
        $validator->validate($value, $constraint);
    }

    public function testValidateSkipsValidationIfValueIsNull(): void
    {
        $value = null;
        $constraint = new OptionalInitials();

        $initialsValidatorMock = $this->createMock(InitialsValidator::class);
        $initialsValidatorMock->expects($this->never())
            ->method('validate');

        $validator = new OptionalInitialsValidator($initialsValidatorMock);
        $validator->initialize($this->createMock(ExecutionContextInterface::class));
        $validator->validate($value, $constraint);
    }

    public function testValidateSkipsValidationIfValueIsEmptyString(): void
    {
        $value = '';
        $constraint = new OptionalInitials();

        $initialsValidatorMock = $this->createMock(InitialsValidator::class);
        $initialsValidatorMock->expects($this->never())
            ->method('validate');

        $validator = new OptionalInitialsValidator($initialsValidatorMock);
        $validator->initialize($this->createMock(ExecutionContextInterface::class));
        $validator->validate($value, $constraint);
    }
}
