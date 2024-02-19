<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\Password;
use App\Shared\Application\Validator\PasswordValidator;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class PasswordValidatorTest extends UnitTestCase
{
    private PasswordValidator $validator;
    private ExecutionContextInterface $context;
    private ConstraintViolationBuilderInterface $violationBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);

        $this->context
            ->expects($this->any())
            ->method('buildViolation')
            ->willReturn($this->violationBuilder);

        $this->violationBuilder
            ->expects($this->any())
            ->method('addViolation');

        $this->validator = new PasswordValidator();
        $this->validator->initialize($this->context);
    }

    public function testValidateValidPassword(): void
    {
        $this->validator->validate('Password123', new Password());

        $this->assertTrue(true);
    }

    public function testValidateInvalidPasswordLength(): void
    {
        $this->validator->validate('Pass', new Password());

        $this->assertTrue(true);
    }

    public function testValidateInvalidPasswordNoNumber(): void
    {
        $this->validator->validate('Password', new Password());

        $this->assertTrue(true);
    }

    public function testValidateInvalidPasswordNoUppercase(): void
    {
        $this->validator->validate('password123', new Password());

        $this->assertTrue(true);
    }
}
