<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\OptionalPassword;
use App\Shared\Application\Validator\OptionalPasswordValidator;
use App\Shared\Application\Validator\PasswordValidator;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class OptionalPasswordValidatorTest extends UnitTestCase
{
    public function testValidate(): void
    {
        $value = $this->faker->word();
        $constraint = new OptionalPassword();

        $passwordValidatorMock = $this->createMock(PasswordValidator::class);
        $passwordValidatorMock->expects($this->once())
            ->method('validate')
            ->with($value, $constraint);

        $validator = new OptionalPasswordValidator($passwordValidatorMock);
        $validator->initialize($this->createMock(ExecutionContextInterface::class));
        $validator->validate($value, $constraint);
    }

    public function testValidateSkipsValidationIfValueIsNull(): void
    {
        $value = null;
        $constraint = new OptionalPassword();

        $passwordValidatorMock = $this->createMock(PasswordValidator::class);
        $passwordValidatorMock->expects($this->never())
            ->method('validate');

        $validator = new OptionalPasswordValidator($passwordValidatorMock);
        $validator->initialize($this->createMock(ExecutionContextInterface::class));
        $validator->validate($value, $constraint);
    }

    public function testValidateSkipsValidationIfValueIsEmptyString(): void
    {
        $value = '';
        $constraint = new OptionalPassword();

        $passwordValidatorMock = $this->createMock(PasswordValidator::class);
        $passwordValidatorMock->expects($this->never())
            ->method('validate');

        $validator = new OptionalPasswordValidator($passwordValidatorMock);
        $validator->initialize($this->createMock(ExecutionContextInterface::class));
        $validator->validate($value, $constraint);
    }
}
