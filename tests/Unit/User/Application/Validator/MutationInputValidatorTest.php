<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Validator;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\MutationInput\MutationInput;
use App\User\Application\Validator\MutationInputValidator;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class MutationInputValidatorTest extends UnitTestCase
{
    private ValidatorInterface $validatorMock;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->validatorMock =
            $this->createMock(ValidatorInterface::class);
    }

    public function testValidateWithNoErrors(): void
    {
        $input = $this->createMock(MutationInput::class);
        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $validator = new MutationInputValidator($this->validatorMock);

        $validator->validate($input);

        $this->addToAssertionCount(1);
    }

    public function testValidateWithErrors(): void
    {
        $input = $this->createMock(MutationInput::class);

        $errors = new ConstraintViolationList([
            $this->createMock(ConstraintViolationInterface::class),
        ]);
        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->willReturn($errors);

        $validator = new MutationInputValidator($this->validatorMock);

        $this->expectException(ValidationException::class);

        $validator->validate($input);
    }
}
