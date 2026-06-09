<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\RequestPasswordResetDto;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RequestPasswordResetDtoTest extends UnitTestCase
{
    private ValidatorInterface $validator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testConstructWithEmail(): void
    {
        $email = $this->faker->safeEmail();

        $dto = new RequestPasswordResetDto($email);

        $this->assertInstanceOf(RequestPasswordResetDto::class, $dto);
        $this->assertSame($email, $dto->email);
    }

    public function testConstructWithDefaults(): void
    {
        $dto = new RequestPasswordResetDto();

        $this->assertInstanceOf(RequestPasswordResetDto::class, $dto);
        $this->assertSame('', $dto->email);
    }

    public function testValidEmailPassesValidation(): void
    {
        $dto = new RequestPasswordResetDto($this->faker->safeEmail());

        $this->assertCount(0, $this->validator->validate($dto));
    }

    public function testBlankEmailFailsValidation(): void
    {
        $dto = new RequestPasswordResetDto('');

        $this->assertGreaterThan(0, $this->validator->validate($dto)->count());
    }

    public function testMalformedEmailFailsValidation(): void
    {
        $dto = new RequestPasswordResetDto('not-an-email');

        $this->assertGreaterThan(0, $this->validator->validate($dto)->count());
    }

    public function testOversizedEmailFailsValidation(): void
    {
        $oversized = str_repeat('a', 250) . '@example.com';

        $dto = new RequestPasswordResetDto($oversized);

        $this->assertGreaterThan(0, $this->validator->validate($dto)->count());
    }
}
