<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Evaluator\CreateUserBatchConstraintEvaluator;
use App\Shared\Application\Validator\Constraint\CreateUserBatch;
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
    private CreateUserBatchConstraintEvaluator $constraintEvaluator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->constraintEvaluator = $this->createMock(CreateUserBatchConstraintEvaluator::class);
        $this->validator = new CreateUserBatchValidator(
            $this->translator,
            $this->constraintEvaluator
        );
        $this->validator->initialize($this->context);
        $this->constraint = $this->createMock(CreateUserBatch::class);
    }

    public function testAddsViolationsReturnedByEvaluator(): void
    {
        $testData = $this->createBatchValidatorTestData();
        $builders = $this->createViolationBuilders();

        $this->setupEvaluatorExpectations($testData['messages']);
        $this->setupTranslatorExpectations($testData['messages'], $testData['translated']);
        $this->setupContextExpectations($testData['translated'], $builders);

        $this->validator->validate('value', $this->constraint);
    }

    public function testSkipsViolationsWhenEvaluatorReturnsNoMessages(): void
    {
        $this->constraintEvaluator->expects($this->once())
            ->method('evaluate')
            ->with('value')
            ->willReturn([]);

        $this->translator->expects($this->never())
            ->method('trans');

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate('value', $this->constraint);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function createBatchValidatorTestData(): array
    {
        return [
            'messages' => ['batch.empty', 'batch.email.duplicate'],
            'translated' => [$this->faker->sentence(), $this->faker->sentence()],
        ];
    }

    /**
     * @return array<int, ConstraintViolationBuilderInterface>
     */
    private function createViolationBuilders(): array
    {
        $firstBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $firstBuilder->expects($this->once())->method('addViolation');

        $secondBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $secondBuilder->expects($this->once())->method('addViolation');

        return [$firstBuilder, $secondBuilder];
    }

    /**
     * @param array<int, string> $messages
     */
    private function setupEvaluatorExpectations(array $messages): void
    {
        $this->constraintEvaluator->expects($this->once())
            ->method('evaluate')
            ->with('value')
            ->willReturn($messages);
    }

    /**
     * @param array<int, string> $messages
     * @param array<int, string> $translated
     */
    private function setupTranslatorExpectations(array $messages, array $translated): void
    {
        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnCallback(
                $this->expectSequential([[$messages[0]], [$messages[1]]], $translated)
            );
    }

    /**
     * @param array<int, string> $translated
     * @param array<int, ConstraintViolationBuilderInterface> $builders
     */
    private function setupContextExpectations(array $translated, array $builders): void
    {
        $this->context->expects($this->exactly(2))
            ->method('buildViolation')
            ->willReturnCallback(
                $this->expectSequential([[$translated[0]], [$translated[1]]], $builders)
            );
    }
}
