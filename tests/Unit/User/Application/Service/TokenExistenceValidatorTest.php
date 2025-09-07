<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Service;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Service\TokenExistenceValidator;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Exception\PasswordResetTokenNotFoundException;

final class TokenExistenceValidatorTest extends UnitTestCase
{
    private TokenExistenceValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new TokenExistenceValidator();
    }

    public function testValidateWithValidToken(): void
    {
        $token = $this->createMock(PasswordResetTokenInterface::class);

        $this->expectNotToPerformAssertions();
        $this->validator->validate($token);
    }

    public function testValidateWithNullTokenThrowsException(): void
    {
        $this->expectException(PasswordResetTokenNotFoundException::class);
        $this->validator->validate(null);
    }
}
