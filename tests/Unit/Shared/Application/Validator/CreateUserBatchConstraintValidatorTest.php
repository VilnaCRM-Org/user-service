<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Normalizer\BatchEntriesNormalizer;
use App\Shared\Application\Provider\BatchEmailProvider;
use App\Shared\Application\Resolver\BatchEmailResolver;
use App\Shared\Application\Validator\Constraint\CreateUserBatch;
use App\Shared\Application\Validator\CreateUserBatchConstraintValidator;
use App\Shared\Application\Validator\CreateUserBatchPayloadValidator;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CreateUserBatchConstraintValidatorTest extends UnitTestCase
{
    private TranslatorInterface $translator;
    private ExecutionContextInterface $context;
    private CreateUserBatchConstraintValidator $validator;
    private Constraint $constraint;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new CreateUserBatchConstraintValidator(
            $this->translator,
            new CreateUserBatchPayloadValidator(
                new BatchEntriesNormalizer(),
                new BatchEmailProvider(new BatchEmailResolver())
            )
        );
        $this->validator->initialize($this->context);
        $this->constraint = $this->createMock(CreateUserBatch::class);
    }

    public function testAddsViolationsReturnedByPayloadValidator(): void
    {
        $payload = $this->createDuplicateEmailPayload();
        $messages = ['batch.email.missing', 'batch.email.duplicate'];
        $translated = [$this->faker->sentence(), $this->faker->sentence()];
        $builders = $this->createViolationBuilders();

        $this->setupTranslatorExpectations($messages, $translated);
        $this->setupContextExpectations($translated, $builders);

        $this->validator->validate($payload, $this->constraint);
    }

    public function testSkipsViolationsWhenPayloadValidatorReturnsNoMessages(): void
    {
        $this->translator->expects($this->never())->method('trans');
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate([['email' => $this->faker->email()]], $this->constraint);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function createDuplicateEmailPayload(): array
    {
        return [
            ['email' => 'USER@example.com'],
            ['email' => 'user@example.com'],
            ['name' => 'Missing'],
        ];
    }

    /**
     * @return array<int, ConstraintViolationBuilderInterface>
     */
    private function createViolationBuilders(): array
    {
        $first = $this->createMock(ConstraintViolationBuilderInterface::class);
        $first->expects($this->once())->method('addViolation');

        $second = $this->createMock(ConstraintViolationBuilderInterface::class);
        $second->expects($this->once())->method('addViolation');

        return [$first, $second];
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
