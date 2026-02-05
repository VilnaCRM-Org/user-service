<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\EmfNamespace;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class EmfNamespaceValidatorInvalidTest extends UnitTestCase
{
    private ValidatorInterface $validator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidator();
    }

    public function testValidatesEmptyNamespace(): void
    {
        $violations = $this->validator->validate('', new EmfNamespace());

        self::assertCount(1, $violations);
        self::assertStringContainsString(
            'non-whitespace character',
            $violations->get(0)->getMessage()
        );
    }

    public function testValidatesWhitespaceOnlyNamespace(): void
    {
        $violations = $this->validator->validate('   ', new EmfNamespace());

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString(
            'non-whitespace character',
            $violations->get(0)->getMessage()
        );
    }

    public function testValidatesNamespaceExceeding256Characters(): void
    {
        $violations = $this->validator->validate(str_repeat('a', 257), new EmfNamespace());

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString(
            'must not exceed 256 characters',
            $violations->get(0)->getMessage()
        );
    }

    public function testValidatesNamespaceWithInvalidCharacters(): void
    {
        $violations = $this->validator->validate('MyApp@Metrics', new EmfNamespace());

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString(
            'alphanumeric characters',
            $violations->get(0)->getMessage()
        );
    }

    public function testValidatesNamespaceWithUnicodeCharacters(): void
    {
        $violations = $this->validator->validate('МоеПриложение/Метрики', new EmfNamespace());

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString(
            'alphanumeric characters',
            $violations->get(0)->getMessage()
        );
    }
}
