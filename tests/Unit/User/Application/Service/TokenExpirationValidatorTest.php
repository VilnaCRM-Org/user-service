<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Service;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Service\TokenExpirationValidator;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Exception\PasswordResetTokenExpiredException;

final class TokenExpirationValidatorTest extends UnitTestCase
{
    private TokenExpirationValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new TokenExpirationValidator();
    }

    public function testValidateWithValidToken(): void
    {
        $token = $this->createMock(PasswordResetTokenInterface::class);
        $token->expects($this->once())
            ->method('isExpired')
            ->willReturn(false);

        // Should not throw any exception
        $this->validator->validate($token);
        $this->addToAssertionCount(1);
    }

    public function testValidateWithExpiredTokenThrowsException(): void
    {
        $token = $this->createMock(PasswordResetTokenInterface::class);
        $token->expects($this->once())
            ->method('isExpired')
            ->willReturn(true);

        $this->expectException(PasswordResetTokenExpiredException::class);
        $this->validator->validate($token);
    }

    public function testValidateWithNullToken(): void
    {
        $this->expectNotToPerformAssertions();
        $this->validator->validate(null);
    }
}
