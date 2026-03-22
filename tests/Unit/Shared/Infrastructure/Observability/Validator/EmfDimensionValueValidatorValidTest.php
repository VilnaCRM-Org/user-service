<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\Validator;

use App\Shared\Infrastructure\Observability\Validator\EmfDimensionValueValidator;
use App\Shared\Infrastructure\Observability\ValueObject\EmfDimensionValue;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class EmfDimensionValueValidatorValidTest extends UnitTestCase
{
    private EmfDimensionValueValidator $service;
    private ValidatorInterface $validator;

    #[\Override]
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
