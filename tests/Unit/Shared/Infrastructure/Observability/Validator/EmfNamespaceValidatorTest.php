<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\Validator;

use App\Shared\Infrastructure\Observability\Exception\InvalidEmfNamespaceException;
use App\Shared\Infrastructure\Observability\Validator\EmfNamespaceValidator;
use App\Shared\Infrastructure\Observability\ValueObject\EmfNamespaceValue;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Validation;

/**
 * Tests EmfNamespaceValidator implementation
 *
 * Following SOLID:
 * - Tests the service that validates EmfNamespaceValue
 * - Verifies proper exception translation from validation violations
 */
final class EmfNamespaceValidatorTest extends UnitTestCase
{
    private EmfNamespaceValidator $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new EmfNamespaceValidator(Validation::createValidator());
    }

    public function testValidatesValidNamespaceWithoutException(): void
    {
        $namespace = new EmfNamespaceValue('MyApp/Metrics');

        $this->service->validate($namespace);

        $this->expectNotToPerformAssertions();
    }

    public function testThrowsExceptionForEmptyNamespace(): void
    {
        $namespace = new EmfNamespaceValue('');

        $this->expectException(InvalidEmfNamespaceException::class);
        $this->expectExceptionMessage('non-whitespace character');

        $this->service->validate($namespace);
    }

    public function testThrowsExceptionForWhitespaceNamespace(): void
    {
        $namespace = new EmfNamespaceValue('   ');

        $this->expectException(InvalidEmfNamespaceException::class);

        $this->service->validate($namespace);
    }

    public function testThrowsExceptionForNamespaceExceeding256Characters(): void
    {
        $namespace = new EmfNamespaceValue(str_repeat('a', 257));

        $this->expectException(InvalidEmfNamespaceException::class);
        $this->expectExceptionMessage('must not exceed 256 characters');

        $this->service->validate($namespace);
    }

    public function testThrowsExceptionForInvalidCharacters(): void
    {
        $namespace = new EmfNamespaceValue('MyApp@Metrics');

        $this->expectException(InvalidEmfNamespaceException::class);
        $this->expectExceptionMessage('alphanumeric characters');

        $this->service->validate($namespace);
    }

    public function testValidatesMaxLengthNamespaceSuccessfully(): void
    {
        $namespace = new EmfNamespaceValue(str_repeat('a', 256));

        $this->service->validate($namespace);

        $this->expectNotToPerformAssertions();
    }

    public function testValidatesNamespaceWithAllAllowedCharacters(): void
    {
        $namespace = new EmfNamespaceValue('ABC-123.abc_xyz/test#v1:prod');

        $this->service->validate($namespace);

        $this->expectNotToPerformAssertions();
    }

    public function testValidatesNamespaceWithSlashes(): void
    {
        $namespace = new EmfNamespaceValue('MyApp/BusinessMetrics/Orders');

        $this->service->validate($namespace);

        $this->expectNotToPerformAssertions();
    }
}
