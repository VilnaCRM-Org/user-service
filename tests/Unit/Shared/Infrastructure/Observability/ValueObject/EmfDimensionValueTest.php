<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\ValueObject;

use App\Shared\Application\Validator\EmfKey;
use App\Shared\Application\Validator\EmfValue;
use App\Shared\Infrastructure\Observability\ValueObject\EmfDimensionValue;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Tests EmfDimensionValue value object and validation using compound constraints.
 *
 * Validation uses EmfKey and EmfValue compound constraints directly,
 * matching how EmfDimensionValueValidator validates.
 */
final class EmfDimensionValueTest extends UnitTestCase
{
    private ValidatorInterface $validator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidator();
    }

    public function testCreatesWithKeyAndValue(): void
    {
        $dimension = new EmfDimensionValue('Endpoint', 'Customer');

        self::assertSame('Endpoint', $dimension->key());
        self::assertSame('Customer', $dimension->value());
    }

    public function testValidatesEmptyKey(): void
    {
        $dimension = new EmfDimensionValue('', 'value');
        $violations = $this->validator->validate($dimension->key(), new EmfKey());

        self::assertCount(1, $violations);
        self::assertStringContainsString('non-whitespace character', $violations->get(0)->getMessage());
    }

    public function testValidatesWhitespaceOnlyKey(): void
    {
        $dimension = new EmfDimensionValue('   ', 'value');
        $violations = $this->validator->validate($dimension->key(), new EmfKey());

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('non-whitespace character', $violations->get(0)->getMessage());
    }

    public function testValidatesKeyExceeding255Characters(): void
    {
        $dimension = new EmfDimensionValue(str_repeat('a', 256), 'value');
        $violations = $this->validator->validate($dimension->key(), new EmfKey());

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('must not exceed 255 characters', $violations->get(0)->getMessage());
    }

    public function testValidatesNonAsciiKey(): void
    {
        $dimension = new EmfDimensionValue('Ключ', 'value');
        $violations = $this->validator->validate($dimension->key(), new EmfKey());

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('ASCII characters', $violations->get(0)->getMessage());
    }

    public function testValidatesKeyWithControlCharacters(): void
    {
        $dimension = new EmfDimensionValue("Key\x00", 'value');
        $violations = $this->validator->validate($dimension->key(), new EmfKey());

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('control characters', $violations->get(0)->getMessage());
    }

    public function testValidatesKeyStartingWithColon(): void
    {
        $dimension = new EmfDimensionValue(':InvalidKey', 'value');
        $violations = $this->validator->validate($dimension->key(), new EmfKey());

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('start with colon', $violations->get(0)->getMessage());
    }

    public function testValidatesEmptyValue(): void
    {
        $dimension = new EmfDimensionValue('Key', '');
        $violations = $this->validator->validate($dimension->value(), new EmfValue());

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('non-whitespace character', $violations->get(0)->getMessage());
    }

    public function testValidatesWhitespaceOnlyValue(): void
    {
        $dimension = new EmfDimensionValue('Key', '   ');
        $violations = $this->validator->validate($dimension->value(), new EmfValue());

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('non-whitespace character', $violations->get(0)->getMessage());
    }

    public function testValidatesValueExceeding1024Characters(): void
    {
        $dimension = new EmfDimensionValue('Key', str_repeat('a', 1025));
        $violations = $this->validator->validate($dimension->value(), new EmfValue());

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('must not exceed 1024 characters', $violations->get(0)->getMessage());
    }

    public function testValidatesNonAsciiValue(): void
    {
        $dimension = new EmfDimensionValue('Key', 'Значение');
        $violations = $this->validator->validate($dimension->value(), new EmfValue());

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('ASCII characters', $violations->get(0)->getMessage());
    }

    public function testValidatesValueWithControlCharacters(): void
    {
        $dimension = new EmfDimensionValue('Key', "Value\x1F");
        $violations = $this->validator->validate($dimension->value(), new EmfValue());

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString('control characters', $violations->get(0)->getMessage());
    }

    public function testAcceptsMaxLengthKey(): void
    {
        $key = str_repeat('a', 255);
        $dimension = new EmfDimensionValue($key, 'value');

        $violations = $this->validator->validate($dimension->key(), new EmfKey());
        self::assertCount(0, $violations);
        self::assertSame($key, $dimension->key());
    }

    public function testAcceptsMaxLengthValue(): void
    {
        $value = str_repeat('a', 1024);
        $dimension = new EmfDimensionValue('Key', $value);

        $violations = $this->validator->validate($dimension->value(), new EmfValue());
        self::assertCount(0, $violations);
        self::assertSame($value, $dimension->value());
    }

    public function testAcceptsColonInMiddleOfKey(): void
    {
        $dimension = new EmfDimensionValue('Key:Name', 'value');

        $violations = $this->validator->validate($dimension->key(), new EmfKey());
        self::assertCount(0, $violations);
        self::assertSame('Key:Name', $dimension->key());
    }
}
