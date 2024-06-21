<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\CreateUserBatch;
use App\Shared\Application\Validator\CreateUserBatchValidator;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CreateUserBatchValidatorTest extends UnitTestCase
{
    private TranslatorInterface $translator;
    private ExecutionContextInterface $context;
    private CreateUserBatchValidator $validator;
    private Constraint $constraint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new CreateUserBatchValidator($this->translator);
        $this->validator->initialize($this->context);
        $this->constraint = $this->createMock(CreateUserBatch::class);
    }

    public function testValidateEmptyBatch(): void
    {
        $message = $this->faker->word();
        $this->translator->method('trans')
            ->with('batch.empty')
            ->willReturn($message);

        $violationBuilder =
            $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($message)
            ->willReturn($violationBuilder);

        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate([], $this->constraint);
    }

    public function testValidateUniqueEmails(): void
    {
        $this->context->expects($this->never())
            ->method('buildViolation');

        $users = [
            ['email' => 'user1@example.com'],
            ['email' => 'user2@example.com'],
            ['email' => 'user3@example.com'],
        ];

        $this->validator->validate($users, $this->constraint);
    }

    public function testValidateDuplicateEmails(): void
    {
        $message = $this->faker->word();
        $this->translator->method('trans')
            ->with('batch.email.duplicate')
            ->willReturn($message);

        $violationBuilder =
            $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($message)
            ->willReturn($violationBuilder);

        $violationBuilder->expects($this->once())
            ->method('addViolation');

        $users = [
            ['email' => 'user1@example.com'],
            ['email' => 'user2@example.com'],
            ['email' => 'user1@example.com'],
        ];

        $this->validator->validate($users, $this->constraint);
    }
}
