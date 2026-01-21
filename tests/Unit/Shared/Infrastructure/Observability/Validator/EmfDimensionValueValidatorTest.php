<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\Validator;

use App\Shared\Infrastructure\Observability\Exception\InvalidEmfDimensionKeyException;
use App\Shared\Infrastructure\Observability\Exception\InvalidEmfDimensionValueException;
use App\Shared\Infrastructure\Observability\Validator\EmfDimensionValueValidator;
use App\Shared\Infrastructure\Observability\ValueObject\EmfDimensionValue;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Tests EmfDimensionValueValidator implementation
 *
 * Following SOLID:
 * - Tests the service that knows about EmfDimensionValue internal structure
 * - Verifies proper exception translation from validation violations
 */
final class EmfDimensionValueValidatorTest extends UnitTestCase
{
    private EmfDimensionValueValidator $service;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidator();
        $this->service = new EmfDimensionValueValidator($this->validator);
    }

    public function testValidatesValidDimensionWithoutException(): void
    {
        $dimension = new EmfDimensionValue('Endpoint', 'Customer');

        $this->service->validate($dimension);

        $this->expectNotToPerformAssertions();
    }

    public function testThrowsInvalidKeyExceptionForEmptyKey(): void
    {
        $dimension = new EmfDimensionValue('', 'value');

        $this->expectException(InvalidEmfDimensionKeyException::class);
        $this->expectExceptionMessage('non-whitespace character');

        $this->service->validate($dimension);
    }

    public function testThrowsInvalidKeyExceptionForWhitespaceKey(): void
    {
        $dimension = new EmfDimensionValue('   ', 'value');

        $this->expectException(InvalidEmfDimensionKeyException::class);

        $this->service->validate($dimension);
    }

    public function testThrowsInvalidKeyExceptionForKeyExceeding255Characters(): void
    {
        $dimension = new EmfDimensionValue(str_repeat('a', 256), 'value');

        $this->expectException(InvalidEmfDimensionKeyException::class);
        $this->expectExceptionMessage('must not exceed 255 characters');

        $this->service->validate($dimension);
    }

    public function testThrowsInvalidKeyExceptionForNonAsciiKey(): void
    {
        $dimension = new EmfDimensionValue('Ключ', 'value');

        $this->expectException(InvalidEmfDimensionKeyException::class);
        $this->expectExceptionMessage('ASCII characters');

        $this->service->validate($dimension);
    }

    public function testThrowsInvalidKeyExceptionForKeyStartingWithColon(): void
    {
        $dimension = new EmfDimensionValue(':InvalidKey', 'value');

        $this->expectException(InvalidEmfDimensionKeyException::class);
        $this->expectExceptionMessage('start with colon');

        $this->service->validate($dimension);
    }

    public function testThrowsInvalidValueExceptionForEmptyValue(): void
    {
        $dimension = new EmfDimensionValue('Key', '');

        $this->expectException(InvalidEmfDimensionValueException::class);
        $this->expectExceptionMessage('non-whitespace character');

        $this->service->validate($dimension);
    }

    public function testThrowsInvalidValueExceptionForWhitespaceValue(): void
    {
        $dimension = new EmfDimensionValue('Key', '   ');

        $this->expectException(InvalidEmfDimensionValueException::class);

        $this->service->validate($dimension);
    }

    public function testThrowsInvalidValueExceptionForValueExceeding1024Characters(): void
    {
        $dimension = new EmfDimensionValue('Key', str_repeat('a', 1025));

        $this->expectException(InvalidEmfDimensionValueException::class);
        $this->expectExceptionMessage('must not exceed 1024 characters');

        $this->service->validate($dimension);
    }

    public function testThrowsInvalidValueExceptionForNonAsciiValue(): void
    {
        $dimension = new EmfDimensionValue('Key', 'Значение');

        $this->expectException(InvalidEmfDimensionValueException::class);
        $this->expectExceptionMessage('ASCII characters');

        $this->service->validate($dimension);
    }

    public function testValidatesMaxLengthKeySuccessfully(): void
    {
        $dimension = new EmfDimensionValue(str_repeat('a', 255), 'value');

        $this->service->validate($dimension);

        $this->expectNotToPerformAssertions();
    }

    public function testValidatesMaxLengthValueSuccessfully(): void
    {
        $dimension = new EmfDimensionValue('Key', str_repeat('a', 1024));

        $this->service->validate($dimension);

        $this->expectNotToPerformAssertions();
    }
}
