<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\OptionalEmail;
use App\Shared\Application\Validator\OptionalEmailValidator;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class OptionalEmailValidatorTest extends UnitTestCase
{
    public function testValidate(): void
    {
        $value = $this->faker->email();
        $emailValidatorMock = $this->createMock(EmailValidator::class);

        $optionalEmailValidator = new OptionalEmailValidator($emailValidatorMock);

        $context = $this->createMock(ExecutionContextInterface::class);
        $emailValidatorMock->expects($this->once())
            ->method('validate')
            ->with($value, new Email());
        $emailValidatorMock->expects($this->once())
            ->method('initialize')
            ->with($context);

        $optionalEmailValidator->initialize($context);
        $constraint = new OptionalEmail();

        $optionalEmailValidator->validate($value, $constraint);
    }

    public function testValidateSkipsValidationIfValueIsNull(): void
    {
        $value = null;
        $constraint = new OptionalEmail();

        $emailValidatorMock = $this->createMock(EmailValidator::class);
        $emailValidatorMock->expects($this->never())
            ->method('validate');

        $validator = new OptionalEmailValidator($emailValidatorMock);
        $validator->initialize($this->createMock(ExecutionContextInterface::class));
        $validator->validate($value, $constraint);
    }

    public function testValidateSkipsValidationIfValueIsEmptyString(): void
    {
        $value = '';
        $constraint = new OptionalEmail();

        $emailValidatorMock = $this->createMock(EmailValidator::class);
        $emailValidatorMock->expects($this->never())
            ->method('validate');

        $validator = new OptionalEmailValidator($emailValidatorMock);
        $validator->initialize($this->createMock(ExecutionContextInterface::class));
        $validator->validate($value, $constraint);
    }
}
