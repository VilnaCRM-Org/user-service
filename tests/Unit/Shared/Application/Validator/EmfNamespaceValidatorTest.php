<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\EmfNamespace;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Tests EmfNamespace validator constraint
 */
final class EmfNamespaceValidatorTest extends UnitTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidator();
    }

    public function testValidatesEmptyNamespace(): void
    {
        $violations = $this->validator->validate('', new EmfNamespace());

        self::assertCount(1, $violations);
        self::assertStringContainsString('non-whitespace character', $violations->get(0)->getMessage());
    }

    public function testValidatesWhitespaceOnlyNamespace(): void
    {
        $violations = $this->validator->validate('   ', new EmfNamespace());

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('non-whitespace character', $violations->get(0)->getMessage());
    }

    public function testValidatesNamespaceExceeding256Characters(): void
    {
        $violations = $this->validator->validate(str_repeat('a', 257), new EmfNamespace());

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('must not exceed 256 characters', $violations->get(0)->getMessage());
    }

    public function testValidatesNamespaceWithInvalidCharacters(): void
    {
        $violations = $this->validator->validate('MyApp@Metrics', new EmfNamespace());

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('alphanumeric characters', $violations->get(0)->getMessage());
    }

    public function testValidatesNamespaceWithUnicodeCharacters(): void
    {
        $violations = $this->validator->validate('МоеПриложение/Метрики', new EmfNamespace());

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('alphanumeric characters', $violations->get(0)->getMessage());
    }

    public function testAcceptsValidNamespaceWithSlashes(): void
    {
        $violations = $this->validator->validate('MyApp/BusinessMetrics', new EmfNamespace());

        self::assertCount(0, $violations);
    }

    public function testAcceptsValidNamespaceWithDashes(): void
    {
        $violations = $this->validator->validate('My-App-Metrics', new EmfNamespace());

        self::assertCount(0, $violations);
    }

    public function testAcceptsValidNamespaceWithUnderscores(): void
    {
        $violations = $this->validator->validate('My_App_Metrics', new EmfNamespace());

        self::assertCount(0, $violations);
    }

    public function testAcceptsValidNamespaceWithDots(): void
    {
        $violations = $this->validator->validate('com.myapp.metrics', new EmfNamespace());

        self::assertCount(0, $violations);
    }

    public function testAcceptsValidNamespaceWithHash(): void
    {
        $violations = $this->validator->validate('MyApp#Metrics', new EmfNamespace());

        self::assertCount(0, $violations);
    }

    public function testAcceptsValidNamespaceWithColon(): void
    {
        $violations = $this->validator->validate('MyApp:Metrics', new EmfNamespace());

        self::assertCount(0, $violations);
    }

    public function testAcceptsValidNamespaceWithAllAllowedCharacters(): void
    {
        $violations = $this->validator->validate('ABC-123.abc_xyz/test#v1:prod', new EmfNamespace());

        self::assertCount(0, $violations);
    }

    public function testAcceptsMaxLengthNamespace(): void
    {
        $namespace = str_repeat('a', 256);
        $violations = $this->validator->validate($namespace, new EmfNamespace());

        self::assertCount(0, $violations);
    }

    public function testAcceptsSingleCharacterNamespace(): void
    {
        $violations = $this->validator->validate('A', new EmfNamespace());

        self::assertCount(0, $violations);
    }
}
