<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\ValueObject;

use App\Shared\Application\Validator\EmfValue;
use App\Shared\Infrastructure\Observability\ValueObject\EmfDimensionValue;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class EmfDimensionValueValueValidationTest extends UnitTestCase
{
    private ValidatorInterface $validator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidator();
    }

    public function testValidatesEmptyValue(): void
    {
        $dimension = new EmfDimensionValue('Key', '');
        $violations = $this->validator->validate($dimension->value(), new EmfValue());

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString(
            'non-whitespace character',
            $violations->get(0)->getMessage()
        );
    }

    public function testValidatesWhitespaceOnlyValue(): void
    {
        $dimension = new EmfDimensionValue('Key', '   ');
        $violations = $this->validator->validate($dimension->value(), new EmfValue());

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString(
            'non-whitespace character',
            $violations->get(0)->getMessage()
        );
    }

    public function testValidatesValueExceeding1024Characters(): void
    {
        $dimension = new EmfDimensionValue('Key', str_repeat('a', 1025));
        $violations = $this->validator->validate($dimension->value(), new EmfValue());

        self::assertGreaterThan(0, $violations->count());
        self::assertStringContainsString(
            'must not exceed 1024 characters',
            $violations->get(0)->getMessage()
        );
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

    public function testAcceptsMaxLengthValue(): void
    {
        $value = str_repeat('a', 1024);
        $dimension = new EmfDimensionValue('Key', $value);

        $violations = $this->validator->validate($dimension->value(), new EmfValue());
        self::assertCount(0, $violations);
        self::assertSame($value, $dimension->value());
    }
}
